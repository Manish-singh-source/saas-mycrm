<?php

namespace App\Http\Controllers\Tenant;

use App\Services\Tenant\TenantWorkspaceService;
use App\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class TenantBusinessController extends BaseTenantController
{
    public function __construct(private readonly TenantWorkspaceService $tenant) {}

    public function financeDashboard(): JsonResponse
    {
        return $this->success(['dashboard' => [
            'cards' => [
                'draft_invoices' => $this->count('tenant_invoices', ['status' => 'draft']),
                'sent_invoices' => $this->count('tenant_invoices', ['status' => 'sent']),
                'open_balance' => (float) $this->base('tenant_invoices')->sum('balance_amount'),
                'payments_this_month' => (float) $this->base('tenant_payments')->whereMonth('paid_at', today()->month)->whereYear('paid_at', today()->year)->sum('amount'),
                'expenses_this_month' => (float) $this->base('tenant_expenses')->whereMonth('expense_date', today()->month)->whereYear('expense_date', today()->year)->sum('amount'),
            ],
            'recent_invoices' => $this->invoiceRows()->orderByDesc('tenant_invoices.id')->limit(8)->get(),
            'recent_payments' => $this->paymentRows()->orderByDesc('tenant_payments.id')->limit(8)->get(),
            'recent_expenses' => $this->expenseRows()->orderByDesc('tenant_expenses.id')->limit(8)->get(),
        ]]);
    }

    public function invoices(Request $request): JsonResponse
    {
        $page = $this->filter($this->invoiceRows(), $request, ['tenant_invoices.invoice_number', 'parties.display_name', 'tenant_invoices.status'])
            ->orderByDesc('tenant_invoices.id')
            ->paginate($request->integer('per_page', 25));

        return $this->list($page->items(), $page);
    }

    public function storeInvoice(Request $request): JsonResponse
    {
        $payload = $this->invoicePayload($request);
        $id = DB::table('tenant_invoices')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'tenant_id' => $this->tenantId(),
            'invoice_number' => $payload['invoice_number'] ?: $this->number('INV'),
            ...Arr::except($payload, ['invoice_number', 'items']),
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        foreach (($payload['items'] ?? []) as $item) {
            $this->insertInvoiceItem($id, $item);
        }
        $this->recalculateInvoice($id);

        return $this->success(['invoice' => $this->invoiceBundle($id)], 'Invoice created.', 201);
    }

    public function showInvoice(string $invoice_uuid): JsonResponse
    {
        return $this->success(['invoice' => $this->invoiceBundle($this->find('tenant_invoices', $invoice_uuid)->id)]);
    }

    public function updateInvoice(Request $request, string $invoice_uuid): JsonResponse
    {
        $invoice = $this->find('tenant_invoices', $invoice_uuid);
        DB::table('tenant_invoices')->where('id', $invoice->id)->update([
            ...$this->invoicePayload($request, true),
            'updated_by' => $request->user()?->id,
            'updated_at' => now(),
        ]);
        $this->recalculateInvoice($invoice->id);

        return $this->success(['invoice' => $this->invoiceBundle($invoice->id)], 'Invoice updated.');
    }

    public function addInvoiceItem(Request $request, string $invoice_uuid): JsonResponse
    {
        $invoice = $this->find('tenant_invoices', $invoice_uuid);
        $itemId = $this->insertInvoiceItem($invoice->id, $request->all());
        $this->recalculateInvoice($invoice->id);

        return $this->success(['item' => DB::table('tenant_invoice_items')->where('id', $itemId)->first(), 'invoice' => $this->invoiceBundle($invoice->id)], 'Line item added.', 201);
    }

    public function updateInvoiceItem(Request $request, string $invoice_uuid, int $item_id): JsonResponse
    {
        $invoice = $this->find('tenant_invoices', $invoice_uuid);
        $this->base('tenant_invoice_items')->where('invoice_id', $invoice->id)->where('id', $item_id)->first() ?: abort(404);
        DB::table('tenant_invoice_items')->where('id', $item_id)->update($this->invoiceItemPayload($request->all()));
        $this->recalculateInvoice($invoice->id);

        return $this->success(['item' => DB::table('tenant_invoice_items')->where('id', $item_id)->first(), 'invoice' => $this->invoiceBundle($invoice->id)], 'Line item updated.');
    }

    public function deleteInvoiceItem(string $invoice_uuid, int $item_id): JsonResponse
    {
        $invoice = $this->find('tenant_invoices', $invoice_uuid);
        DB::table('tenant_invoice_items')->where('tenant_id', $this->tenantId())->where('invoice_id', $invoice->id)->where('id', $item_id)->delete();
        $this->recalculateInvoice($invoice->id);

        return $this->success(['invoice' => $this->invoiceBundle($invoice->id)], 'Line item removed.');
    }

    public function sendInvoice(Request $request, string $invoice_uuid): JsonResponse
    {
        $invoice = $this->find('tenant_invoices', $invoice_uuid);
        DB::table('tenant_invoices')->where('id', $invoice->id)->update(['status' => 'sent', 'updated_at' => now()]);
        $this->logCommunication($request, 'email', 'outbound', $request->input('subject', 'Invoice '.$invoice->invoice_number), $request->input('body', 'Invoice sent.'), 'queued', $invoice->client_party_id);

        return $this->success(['invoice' => $this->invoiceBundle($invoice->id)], 'Invoice email queued.', 202);
    }

    public function cancelInvoice(Request $request, string $invoice_uuid): JsonResponse
    {
        $invoice = $this->find('tenant_invoices', $invoice_uuid);
        DB::table('tenant_invoices')->where('id', $invoice->id)->update(['status' => 'cancelled', 'updated_at' => now()]);

        return $this->success(['invoice' => $this->invoiceBundle($invoice->id)], 'Invoice cancelled.');
    }

    public function invoicePdf(string $invoice_uuid): JsonResponse
    {
        $invoice = $this->find('tenant_invoices', $invoice_uuid);

        return $this->success(['invoice' => $this->invoiceBundle($invoice->id), 'placeholder' => true, 'message' => 'PDF file generation is not configured yet. The invoice preview uses live invoice data.']);
    }

    public function exportInvoices(Request $request): JsonResponse { return $this->queued($request, 'export', 'invoices'); }

    public function payments(Request $request): JsonResponse
    {
        $page = $this->filter($this->paymentRows(), $request, ['tenant_payments.payment_number', 'tenant_payments.reference', 'parties.display_name'])
            ->orderByDesc('tenant_payments.id')
            ->paginate($request->integer('per_page', 25));

        return $this->list($page->items(), $page);
    }

    public function storePayment(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'invoice_uuid' => ['nullable'],
            'client_party_uuid' => ['nullable'],
            'payment_number' => ['nullable', 'string', 'max:80'],
            'amount' => ['required', 'numeric'],
            'currency' => ['nullable', 'string', 'size:3'],
            'method' => ['nullable', 'string', 'max:80'],
            'reference' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:50'],
            'paid_at' => ['nullable', 'date'],
        ]);
        $invoice = $this->byUuid('tenant_invoices', $payload['invoice_uuid'] ?? null);
        $partyId = $invoice?->client_party_id ?: $this->partyId($payload['client_party_uuid'] ?? null, null);
        $id = DB::table('tenant_payments')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'tenant_id' => $this->tenantId(),
            'invoice_id' => $invoice?->id,
            'client_party_id' => $partyId,
            'payment_number' => $payload['payment_number'] ?? $this->number('PAY'),
            'amount' => $payload['amount'],
            'currency' => $payload['currency'] ?? 'INR',
            'method' => $payload['method'] ?? null,
            'reference' => $payload['reference'] ?? null,
            'status' => $payload['status'] ?? 'paid',
            'paid_at' => $payload['paid_at'] ?? now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        if ($invoice) $this->recalculateInvoice($invoice->id);

        return $this->success(['payment' => $this->paymentRows()->where('tenant_payments.id', $id)->first()], 'Payment recorded.', 201);
    }

    public function showPayment(string $payment_uuid): JsonResponse
    {
        $payment = $this->find('tenant_payments', $payment_uuid);

        return $this->success(['payment' => $this->paymentRows()->where('tenant_payments.id', $payment->id)->first()]);
    }

    public function voidPayment(Request $request, string $payment_uuid): JsonResponse
    {
        $payment = $this->find('tenant_payments', $payment_uuid);
        DB::table('tenant_payments')->where('id', $payment->id)->update(['status' => 'void', 'updated_at' => now()]);
        if ($payment->invoice_id) $this->recalculateInvoice((int) $payment->invoice_id);

        return $this->success(['payment' => $this->paymentRows()->where('tenant_payments.id', $payment->id)->first()], 'Payment voided.');
    }

    public function paymentReceipt(string $payment_uuid): JsonResponse
    {
        $payment = $this->find('tenant_payments', $payment_uuid);

        return $this->success(['payment' => $this->paymentRows()->where('tenant_payments.id', $payment->id)->first(), 'placeholder' => true, 'message' => 'Receipt file upload is not configured on tenant_payments; use Documents to attach receipts to related records.']);
    }

    public function exportPayments(Request $request): JsonResponse { return $this->queued($request, 'export', 'payments'); }

    public function expenses(Request $request): JsonResponse
    {
        $page = $this->filter($this->expenseRows(), $request, ['tenant_expenses.expense_number', 'vendor.display_name', 'project.name'])
            ->orderByDesc('tenant_expenses.id')
            ->paginate($request->integer('per_page', 25));

        return $this->list($page->items(), $page);
    }

    public function storeExpense(Request $request): JsonResponse
    {
        $payload = $this->expensePayload($request);
        $id = DB::table('tenant_expenses')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'tenant_id' => $this->tenantId(),
            'expense_number' => $payload['expense_number'] ?: $this->number('EXP'),
            ...Arr::except($payload, ['expense_number', 'items']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        foreach (($payload['items'] ?? []) as $item) {
            DB::table('tenant_expense_items')->insert(['tenant_id' => $this->tenantId(), 'expense_id' => $id, ...$this->expenseItemPayload($item)]);
        }

        return $this->success(['expense' => $this->expenseBundle($id)], 'Expense created.', 201);
    }

    public function showExpense(string $expense_uuid): JsonResponse
    {
        return $this->success(['expense' => $this->expenseBundle($this->find('tenant_expenses', $expense_uuid)->id)]);
    }

    public function updateExpense(Request $request, string $expense_uuid): JsonResponse
    {
        $expense = $this->find('tenant_expenses', $expense_uuid);
        DB::table('tenant_expenses')->where('id', $expense->id)->update([...Arr::except($this->expensePayload($request, true), ['items']), 'updated_at' => now()]);

        return $this->success(['expense' => $this->expenseBundle($expense->id)], 'Expense updated.');
    }

    public function approveExpense(Request $request, string $expense_uuid): JsonResponse { return $this->expenseStatus($expense_uuid, 'approved'); }
    public function rejectExpense(Request $request, string $expense_uuid): JsonResponse { return $this->expenseStatus($expense_uuid, 'rejected'); }
    public function exportExpenses(Request $request): JsonResponse { return $this->queued($request, 'export', 'expenses'); }

    public function bankAccounts(Request $request): JsonResponse
    {
        if ($request->isMethod('post')) {
            $payload = $this->bankPayload($request);
            $id = DB::table('bank_accounts')->insertGetId(['tenant_id' => $this->tenantId(), ...$payload, 'created_at' => now(), 'updated_at' => now()]);
            if ($payload['is_primary'] ?? false) $this->clearOtherPrimary($id, $payload['owner_type'], $payload['owner_id']);

            return $this->success(['bank_account' => $this->bankAccount($id)], 'Bank account saved.', 201);
        }

        $page = $this->base('bank_accounts')->orderByDesc('id')->paginate($request->integer('per_page', 25));
        $items = collect($page->items())->map(fn ($row) => $this->bankAccount((int) $row->id))->all();

        return $this->list($items, $page);
    }

    public function updateBankAccount(Request $request, int $account_id): JsonResponse
    {
        $row = $this->scoped('bank_accounts', $account_id);
        $payload = $this->bankPayload($request, true);
        DB::table('bank_accounts')->where('id', $row->id)->update([...$payload, 'updated_at' => now()]);
        if (($payload['is_primary'] ?? false) === true) $this->clearOtherPrimary($row->id, $payload['owner_type'] ?? $row->owner_type, $payload['owner_id'] ?? $row->owner_id);

        return $this->success(['bank_account' => $this->bankAccount($row->id)], 'Bank account updated.');
    }

    public function deleteBankAccount(int $account_id): JsonResponse
    {
        $this->scoped('bank_accounts', $account_id);
        DB::table('bank_accounts')->where('id', $account_id)->delete();

        return $this->success(null, 'Bank account deleted.');
    }

    public function setPrimaryBankAccount(int $account_id): JsonResponse
    {
        $row = $this->scoped('bank_accounts', $account_id);
        $this->clearOtherPrimary($row->id, $row->owner_type, $row->owner_id);
        DB::table('bank_accounts')->where('id', $row->id)->update(['is_primary' => true, 'updated_at' => now()]);

        return $this->success(['bank_account' => $this->bankAccount($row->id)], 'Primary bank account updated.');
    }

    public function documentDashboard(Request $request): JsonResponse
    {
        $page = $this->base('files')->orderByDesc('id')->paginate($request->integer('per_page', 25));
        return $this->list(collect($page->items())->map(fn ($file) => $this->filePayload($file))->all(), $page, 'OK', [
            'summary' => [
                'total_files' => $this->count('files'),
                'shared_files' => $this->base('files')->where('visibility', 'tenant')->count(),
                'recent_files' => $this->base('files')->where('created_at', '>=', now()->subDays(7))->count(),
            ],
        ]);
    }

    public function folders(Request $request): JsonResponse
    {
        if ($request->isMethod('post')) {
            $data = $request->validate(['name' => ['required', 'string', 'max:150'], 'parent_uuid' => ['nullable'], 'folder_type' => ['nullable', 'string', 'max:80']]);
            $parent = $this->byUuid('document_folders', $data['parent_uuid'] ?? null);
            $id = DB::table('document_folders')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => $this->tenantId(),
                'parent_id' => $parent?->id,
                'name' => $data['name'],
                'slug' => Str::slug($data['name']).'-'.Str::lower(Str::random(5)),
                'folder_type' => $data['folder_type'] ?? 'general',
                'created_by' => $request->user()?->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            return $this->success(['folder' => DB::table('document_folders')->where('id', $id)->first()], 'Folder created.', 201);
        }

        return $this->success(['folders' => $this->base('document_folders')->orderBy('name')->get()]);
    }

    public function attachFileToFolder(Request $request, string $folder_uuid): JsonResponse
    {
        $folder = $this->find('document_folders', $folder_uuid);
        $file = $this->find('files', $request->validate(['file_uuid' => ['required', 'uuid']])['file_uuid']);
        DB::table('document_folder_files')->updateOrInsert(['document_folder_id' => $folder->id, 'file_id' => $file->id], ['tenant_id' => $this->tenantId(), 'created_by' => $request->user()?->id, 'created_at' => now(), 'updated_at' => now()]);

        return $this->success(['files' => $this->folderFiles($folder->id)], 'File attached to folder.');
    }

    public function reportsDashboard(): JsonResponse
    {
        return $this->success(['dashboard' => [
            'cards' => [
                'crm_clients' => $this->base('parties')->where('party_type', 'client')->count(),
                'crm_leads' => $this->base('parties')->where('party_type', 'lead')->count(),
                'staff' => $this->count('staff'),
                'open_projects' => $this->count('projects'),
                'invoice_balance' => (float) $this->base('tenant_invoices')->sum('balance_amount'),
            ],
            'available_reports' => collect($this->reportCodes())->map(fn ($label, $code) => ['code' => $code, 'name' => $label])->values(),
            'recent_exports' => $this->base('tenant_import_export_jobs')->where('module', 'like', 'report_%')->orderByDesc('id')->limit(10)->get(),
        ]]);
    }

    public function report(Request $request, string $report_code): JsonResponse
    {
        return $this->success(['report' => ['code' => $report_code, 'name' => $this->reportCodes()[$report_code] ?? Str::headline($report_code)], 'rows' => $this->reportRows($report_code, $request)]);
    }

    public function exportReport(Request $request, string $report_code): JsonResponse { return $this->queued($request, 'export', 'report_'.$report_code); }
    public function customReports(Request $request): JsonResponse { return $this->success(['custom_reports' => [], 'placeholder' => true, 'message' => 'Custom report designer table is not present yet.']); }
    public function storeCustomReport(Request $request): JsonResponse { return $this->success(['placeholder' => true, 'payload' => $request->only(['name', 'module', 'filters', 'columns'])], 'Custom report storage is not configured yet.', 202); }
    public function runCustomReport(string $report_uuid): JsonResponse { return $this->success(['rows' => [], 'placeholder' => true, 'message' => 'Custom report storage is not configured yet.']); }

    public function settingsGroup(Request $request, string $group): JsonResponse
    {
        if ($request->isMethod('put') || $request->isMethod('patch')) {
            foreach ((array) $request->input('settings', $request->all()) as $key => $value) {
                DB::table('tenant_settings')->updateOrInsert(
                    ['tenant_id' => $this->tenantId(), 'group' => $group, 'key' => $key],
                    ['value' => json_encode($value), 'value_type' => gettype($value), 'updated_by' => $request->user()?->id, 'created_at' => now(), 'updated_at' => now()]
                );
            }
        }

        return $this->success(['group' => $group, 'settings' => $this->settingsRows($group)]);
    }

    public function settingsLookups(Request $request): JsonResponse
    {
        return $this->success(['lookups' => $this->base('tenant_lookups')->orderBy('group')->orderBy('sort_order')->get()]);
    }

    public function reorderLookups(Request $request): JsonResponse
    {
        foreach ((array) $request->input('items', []) as $index => $item) {
            DB::table('tenant_lookups')->where('tenant_id', $this->tenantId())->where('uuid', $item['uuid'] ?? null)->update(['sort_order' => $item['sort_order'] ?? $index + 1, 'updated_at' => now()]);
        }
        return $this->settingsLookups($request);
    }

    public function deleteLookup(string $lookup_uuid): JsonResponse
    {
        $lookup = $this->find('tenant_lookups', $lookup_uuid);
        $used = collect(['parties' => ['source_id', 'status_id'], 'projects' => ['category_id', 'type_id', 'status_id', 'priority_id'], 'tenant_expenses' => ['category_id', 'status_id']])
            ->sum(fn ($columns, $table) => collect($columns)->sum(fn ($column) => Schema::hasColumn($table, $column) ? $this->base($table)->where($column, $lookup->id)->count() : 0));
        abort_if($used > 0, 409, "Lookup is used by {$used} records.");
        DB::table('tenant_lookups')->where('id', $lookup->id)->delete();

        return $this->success(null, 'Lookup deleted.');
    }

    public function notificationTemplates(Request $request): JsonResponse
    {
        if ($request->isMethod('post')) {
            $data = $request->validate(['code' => ['required', 'string', 'max:120'], 'channel' => ['required', 'string', 'max:50'], 'subject' => ['nullable', 'string'], 'body' => ['required', 'string'], 'variables' => ['nullable', 'array'], 'status' => ['nullable', 'string']]);
            $id = DB::table('notification_templates')->insertGetId(['uuid' => (string) Str::uuid(), 'tenant_id' => $this->tenantId(), ...Arr::except($data, ['variables']), 'variables' => isset($data['variables']) ? json_encode($data['variables']) : null, 'status' => $data['status'] ?? 'active', 'created_at' => now(), 'updated_at' => now()]);
            return $this->success(['template' => DB::table('notification_templates')->where('id', $id)->first()], 'Template saved.', 201);
        }
        $page = $this->base('notification_templates')->orWhereNull('tenant_id')->orderByDesc('tenant_id')->orderBy('code')->paginate($request->integer('per_page', 25));
        return $this->list($page->items(), $page);
    }

    public function updateNotificationTemplate(Request $request, string $template_uuid): JsonResponse
    {
        $template = DB::table('notification_templates')->where(fn ($q) => $q->where('tenant_id', $this->tenantId())->orWhereNull('tenant_id'))->where('uuid', $template_uuid)->first() ?: abort(404);
        $data = $request->only(['subject', 'body', 'variables', 'status']);
        if (isset($data['variables'])) $data['variables'] = json_encode($data['variables']);
        DB::table('notification_templates')->where('id', $template->id)->update([...$data, 'tenant_id' => $this->tenantId(), 'updated_at' => now()]);

        return $this->success(['template' => DB::table('notification_templates')->where('id', $template->id)->first()], 'Template updated.');
    }

    public function testNotificationTemplate(Request $request, string $template_uuid): JsonResponse
    {
        $template = DB::table('notification_templates')->where(fn ($q) => $q->where('tenant_id', $this->tenantId())->orWhereNull('tenant_id'))->where('uuid', $template_uuid)->first() ?: abort(404);
        $logId = $this->logCommunication($request, $template->channel, 'outbound', $template->subject ?: 'Template test', $template->body, 'queued');

        return $this->success(['log' => DB::table('communication_logs')->where('id', $logId)->first()], 'Template test queued.', 202);
    }

    public function backupRuns(Request $request): JsonResponse
    {
        $page = $this->base('tenant_backup_runs')->orderByDesc('id')->paginate($request->integer('per_page', 25));
        return $this->list($page->items(), $page);
    }

    public function runBackup(Request $request): JsonResponse
    {
        $id = DB::table('tenant_backup_runs')->insertGetId(['uuid' => (string) Str::uuid(), 'tenant_id' => $this->tenantId(), 'backup_type' => $request->input('backup_type', 'manual'), 'status' => 'queued', 'started_at' => now()]);
        return $this->success(['backup' => DB::table('tenant_backup_runs')->where('id', $id)->first()], 'Backup queued.', 202);
    }

    public function restoreBackup(Request $request): JsonResponse
    {
        $backup = $this->byUuid('tenant_backup_runs', $request->input('backup_uuid'));
        $id = DB::table('tenant_restore_requests')->insertGetId(['uuid' => (string) Str::uuid(), 'tenant_id' => $this->tenantId(), 'tenant_backup_run_id' => $backup?->id, 'status' => 'pending', 'requested_by' => $request->user()?->id, 'requested_at' => now()]);
        return $this->success(['restore_request' => DB::table('tenant_restore_requests')->where('id', $id)->first()], 'Restore request submitted.', 202);
    }

    public function providers(Request $request): JsonResponse
    {
        $page = DB::table('integration_providers')->where('status', 'active')->orderBy('category')->orderBy('name')->paginate($request->integer('per_page', 25));
        return $this->list($page->items(), $page);
    }

    public function integrations(Request $request): JsonResponse
    {
        if ($request->isMethod('post')) {
            $data = $request->validate(['provider_id' => ['required'], 'name' => ['required', 'string'], 'credentials' => ['nullable', 'array']]);
            $provider = is_numeric($data['provider_id']) ? DB::table('integration_providers')->where('id', $data['provider_id'])->first() : DB::table('integration_providers')->where('code', $data['provider_id'])->orWhere('id', $data['provider_id'])->first();
            abort_if(! $provider, 404, 'Provider not found.');
            $id = DB::table('tenant_integrations')->insertGetId(['uuid' => (string) Str::uuid(), 'tenant_id' => $this->tenantId(), 'provider_id' => $provider->id, 'name' => $data['name'], 'status' => 'active', 'connected_by' => $request->user()?->id, 'connected_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
            $this->storeCredentials($id, $data['credentials'] ?? []);
            return $this->success(['integration' => $this->integrationBundle($id)], 'Integration connected.', 201);
        }
        $page = $this->integrationRows()->paginate($request->integer('per_page', 25));
        return $this->list($page->items(), $page);
    }

    public function showIntegration(string $integration_uuid): JsonResponse { return $this->success(['integration' => $this->integrationBundle($this->find('tenant_integrations', $integration_uuid)->id)]); }
    public function updateIntegration(Request $request, string $integration_uuid): JsonResponse { $i = $this->find('tenant_integrations', $integration_uuid); DB::table('tenant_integrations')->where('id', $i->id)->update([...$request->only(['name', 'status']), 'updated_at' => now()]); return $this->success(['integration' => $this->integrationBundle($i->id)], 'Integration updated.'); }
    public function rotateCredentials(Request $request, string $integration_uuid): JsonResponse { $i = $this->find('tenant_integrations', $integration_uuid); $this->storeCredentials($i->id, $request->validate(['credentials' => ['required', 'array']])['credentials']); return $this->success(['credentials' => DB::table('integration_credentials')->where('tenant_integration_id', $i->id)->get(['key', 'expires_at'])], 'Credentials rotated.'); }
    public function disconnectIntegration(string $integration_uuid): JsonResponse { $i = $this->find('tenant_integrations', $integration_uuid); DB::table('tenant_integrations')->where('id', $i->id)->update(['status' => 'disconnected', 'updated_at' => now()]); return $this->success(['integration' => $this->integrationBundle($i->id)], 'Integration disconnected.'); }
    public function webhooks(Request $request): JsonResponse { return $this->integrationChildIndex($request, 'integration_webhooks', 'webhooks'); }
    public function syncJobs(Request $request): JsonResponse { return $this->integrationChildIndex($request, 'integration_sync_jobs', 'sync_jobs'); }
    public function retrySyncJob(int $job_id): JsonResponse { DB::table('integration_sync_jobs')->whereIn('tenant_integration_id', $this->base('tenant_integrations')->pluck('id'))->where('id', $job_id)->update(['status' => 'retry_queued']); return $this->success(['job' => DB::table('integration_sync_jobs')->where('id', $job_id)->first()], 'Sync retry queued.'); }
    public function mappings(string $integration_uuid): JsonResponse { $i = $this->find('tenant_integrations', $integration_uuid); return $this->success(['mappings' => DB::table('integration_field_mappings')->where('tenant_integration_id', $i->id)->get()]); }
    public function replaceMappings(Request $request, string $integration_uuid): JsonResponse { $i = $this->find('tenant_integrations', $integration_uuid); DB::table('integration_field_mappings')->where('tenant_integration_id', $i->id)->delete(); foreach ((array) $request->input('mappings', []) as $m) DB::table('integration_field_mappings')->insert(['tenant_integration_id' => $i->id, 'entity_type' => $m['entity_type'] ?? 'record', 'local_field' => $m['local_field'] ?? '', 'external_field' => $m['external_field'] ?? '', 'transform_rule' => isset($m['transform_rule']) ? json_encode($m['transform_rule']) : null]); return $this->mappings($integration_uuid); }
    public function rateLimits(string $integration_uuid): JsonResponse { $i = $this->find('tenant_integrations', $integration_uuid); return $this->success(['rate_limits' => DB::table('integration_rate_limits')->where('tenant_integration_id', $i->id)->orderByDesc('id')->get()]); }

    public function audit(Request $request, string $type): JsonResponse
    {
        $map = [
            'activity-logs' => fn () => $this->base('activity_logs')->orderByDesc('id'),
            'login-history' => fn () => $this->base('security_events')->where('event', 'like', '%login%')->orderByDesc('id'),
            'system-api-logs' => fn () => $this->base('api_request_logs')->orderByDesc('id'),
            'data-changes' => fn () => $this->base('activity_logs')->where(fn ($q) => $q->whereNotNull('old_values')->orWhereNotNull('new_values'))->orderByDesc('id'),
        ];
        abort_if(! isset($map[$type]), 404);
        $page = $map[$type]()->paginate($request->integer('per_page', 25));
        return $this->list($page->items(), $page);
    }

    public function compareAudit(int $id): JsonResponse
    {
        $log = $this->base('activity_logs')->where('id', $id)->first() ?: abort(404);
        $old = json_decode($log->old_values ?: '[]', true) ?: [];
        $new = json_decode($log->new_values ?: '[]', true) ?: [];
        return $this->success(['activity' => $log, 'compare' => ['old_values' => $old, 'new_values' => $new, 'changed_fields' => array_values(array_filter(array_unique([...array_keys($old), ...array_keys($new)]), fn ($key) => ($old[$key] ?? null) !== ($new[$key] ?? null)))]]);
    }

    public function exportAudit(Request $request): JsonResponse { return $this->queued($request, 'export', 'audit'); }

    public function selectors(): JsonResponse
    {
        return $this->success([
            'users' => $this->base('users')->orderBy('display_name')->limit(200)->get(['uuid', 'display_name', 'email']),
            'clients' => $this->base('parties')->where('party_type', 'client')->orderBy('display_name')->limit(200)->get(['uuid', 'display_name', 'email', 'phone']),
            'vendors' => $this->base('parties')->where('party_type', 'vendor')->orderBy('display_name')->limit(200)->get(['uuid', 'display_name', 'email', 'phone']),
            'projects' => $this->base('projects')->orderBy('name')->limit(200)->get(['uuid', 'name', 'project_number']),
            'accounts' => collect($this->base('bank_accounts')->orderBy('bank_name')->limit(200)->get())->map(fn ($row) => $this->bankAccount((int) $row->id))->all(),
            'providers' => DB::table('integration_providers')->where('status', 'active')->orderBy('name')->limit(200)->get(['id', 'name', 'code', 'category']),
            'lookups' => $this->base('tenant_lookups')->orderBy('group')->orderBy('name')->limit(400)->get(['uuid', 'id', 'group', 'name', 'code']),
        ]);
    }

    private function tenantId(): int { return app(TenantContext::class)->id(); }
    private function base(string $table) { $query = DB::table($table); if (Schema::hasColumn($table, 'tenant_id')) $query->where($table.'.tenant_id', $this->tenantId()); if (Schema::hasColumn($table, 'deleted_at')) $query->whereNull($table.'.deleted_at'); return $query; }
    private function find(string $table, string $uuid): object { return $this->base($table)->where($table.'.uuid', $uuid)->first() ?: abort(404, 'Resource not found.'); }
    private function byUuid(string $table, mixed $uuid): ?object { return $uuid ? $this->find($table, (string) $uuid) : null; }
    private function scoped(string $table, int $id): object { return $this->base($table)->where($table.'.id', $id)->first() ?: abort(404); }
    private function count(string $table, array $where = []): int { $q = $this->base($table); foreach ($where as $column => $value) $q->where($column, $value); return $q->count(); }
    private function idFrom(string $table, mixed $value): ?int { if ($value === null || $value === '') return null; if (is_numeric($value)) return (int) $value; return (int) $this->find($table, (string) $value)->id; }
    private function partyId(mixed $uuid, ?string $type): ?int { if (! $uuid) return null; $q = $this->base('parties')->where('uuid', $uuid); if ($type) $q->where('party_type', $type); return (int) ($q->value('id') ?: abort(404, 'Party not found.')); }
    private function filter($query, Request $request, array $columns) { if ($search = $request->input('search')) $query->where(fn ($inner) => collect($columns)->each(fn ($column) => $inner->orWhere($column, 'like', '%'.$search.'%'))); return $query; }
    private function queued(Request $request, string $type, string $module): JsonResponse { return $this->success(['job' => $this->tenant->createJob($request, $type, $module, $request->all())], Str::headline($module).' '.$type.' queued.', 202); }
    private function number(string $prefix): string { return $prefix.'-'.now()->format('ymd').'-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT); }

    private function invoiceRows() { return $this->base('tenant_invoices')->join('parties', 'parties.id', '=', 'tenant_invoices.client_party_id')->leftJoin('projects', 'projects.id', '=', 'tenant_invoices.project_id')->select('tenant_invoices.*', 'parties.uuid as client_party_uuid', 'parties.display_name as client_name', 'projects.uuid as project_uuid', 'projects.name as project_name'); }
    private function paymentRows() { return $this->base('tenant_payments')->leftJoin('tenant_invoices', 'tenant_invoices.id', '=', 'tenant_payments.invoice_id')->leftJoin('parties', 'parties.id', '=', 'tenant_payments.client_party_id')->select('tenant_payments.*', 'tenant_invoices.uuid as invoice_uuid', 'tenant_invoices.invoice_number', 'parties.uuid as client_party_uuid', 'parties.display_name as client_name'); }
    private function expenseRows() { return $this->base('tenant_expenses')->leftJoin('parties as vendor', 'vendor.id', '=', 'tenant_expenses.vendor_party_id')->leftJoin('projects as project', 'project.id', '=', 'tenant_expenses.project_id')->leftJoin('tenant_lookups as category', 'category.id', '=', 'tenant_expenses.category_id')->leftJoin('tenant_lookups as status', 'status.id', '=', 'tenant_expenses.status_id')->select('tenant_expenses.*', 'vendor.uuid as vendor_party_uuid', 'vendor.display_name as vendor_name', 'project.uuid as project_uuid', 'project.name as project_name', 'category.name as category_name', 'status.name as status_name'); }
    private function invoiceBundle(int $id): array { return ['record' => $this->invoiceRows()->where('tenant_invoices.id', $id)->first(), 'items' => $this->base('tenant_invoice_items')->where('invoice_id', $id)->get(), 'payments' => $this->paymentRows()->where('tenant_payments.invoice_id', $id)->get()]; }
    private function expenseBundle(int $id): array { return ['record' => $this->expenseRows()->where('tenant_expenses.id', $id)->first(), 'items' => $this->base('tenant_expense_items')->where('expense_id', $id)->get()]; }

    private function invoicePayload(Request $request, bool $partial = false): array
    {
        $data = $request->only(['invoice_number', 'client_party_uuid', 'project_uuid', 'invoice_date', 'due_date', 'discount_amount', 'tax_amount', 'currency', 'status', 'items']);
        if (! $partial) $data += ['invoice_number' => '', 'invoice_date' => today()->toDateString(), 'discount_amount' => 0, 'tax_amount' => 0, 'currency' => 'INR', 'status' => 'draft'];
        if (array_key_exists('client_party_uuid', $data)) { $data['client_party_id'] = $this->partyId($data['client_party_uuid'], 'client'); unset($data['client_party_uuid']); }
        if (array_key_exists('project_uuid', $data)) { $data['project_id'] = $this->idFrom('projects', $data['project_uuid']); unset($data['project_uuid']); }
        return array_filter($data, fn ($value) => $value !== null && $value !== '');
    }

    private function insertInvoiceItem(int $invoiceId, array $item): int
    {
        return DB::table('tenant_invoice_items')->insertGetId(['tenant_id' => $this->tenantId(), 'invoice_id' => $invoiceId, ...$this->invoiceItemPayload($item)]);
    }

    private function invoiceItemPayload(array $item): array
    {
        $quantity = (float) ($item['quantity'] ?? 1);
        $unit = (float) ($item['unit_price'] ?? 0);
        $taxRate = (float) ($item['tax_rate'] ?? 0);
        return ['item_name' => $item['item_name'] ?? $item['name'] ?? 'Line item', 'description' => $item['description'] ?? null, 'quantity' => $quantity, 'unit_price' => $unit, 'tax_rate' => $taxRate, 'amount' => round($quantity * $unit * (1 + $taxRate / 100), 2)];
    }

    private function recalculateInvoice(int $invoiceId): void
    {
        $items = $this->base('tenant_invoice_items')->where('invoice_id', $invoiceId)->get();
        $subtotal = (float) $items->sum(fn ($item) => (float) $item->quantity * (float) $item->unit_price);
        $tax = (float) $items->sum(fn ($item) => ((float) $item->quantity * (float) $item->unit_price) * ((float) $item->tax_rate / 100));
        $discount = (float) (DB::table('tenant_invoices')->where('id', $invoiceId)->value('discount_amount') ?: 0);
        $paid = (float) $this->base('tenant_payments')->where('invoice_id', $invoiceId)->whereNotIn('status', ['void', 'cancelled'])->sum('amount');
        $total = max($subtotal + $tax - $discount, 0);
        DB::table('tenant_invoices')->where('id', $invoiceId)->update(['subtotal' => $subtotal, 'taxable_amount' => $subtotal, 'tax_amount' => $tax, 'total_amount' => $total, 'paid_amount' => $paid, 'balance_amount' => max($total - $paid, 0), 'updated_at' => now()]);
    }

    private function expensePayload(Request $request, bool $partial = false): array
    {
        $data = $request->only(['expense_number', 'vendor_party_uuid', 'project_uuid', 'category_id', 'amount', 'currency', 'expense_date', 'status_id', 'items']);
        if (! $partial) $data += ['expense_number' => '', 'amount' => 0, 'currency' => 'INR', 'expense_date' => today()->toDateString()];
        if (array_key_exists('vendor_party_uuid', $data)) { $data['vendor_party_id'] = $this->partyId($data['vendor_party_uuid'], 'vendor'); unset($data['vendor_party_uuid']); }
        if (array_key_exists('project_uuid', $data)) { $data['project_id'] = $this->idFrom('projects', $data['project_uuid']); unset($data['project_uuid']); }
        foreach (['category_id', 'status_id'] as $field) if (array_key_exists($field, $data)) $data[$field] = $this->idFrom('tenant_lookups', $data[$field]);
        return array_filter($data, fn ($value) => $value !== null && $value !== '');
    }

    private function expenseItemPayload(array $item): array
    {
        $quantity = (float) ($item['quantity'] ?? 1);
        $unit = (float) ($item['unit_price'] ?? $item['amount'] ?? 0);
        return ['description' => $item['description'] ?? 'Expense item', 'quantity' => $quantity, 'unit_price' => $unit, 'tax_amount' => (float) ($item['tax_amount'] ?? 0), 'amount' => (float) ($item['amount'] ?? $quantity * $unit)];
    }

    private function expenseStatus(string $uuid, string $status): JsonResponse
    {
        $expense = $this->find('tenant_expenses', $uuid);
        $lookup = $this->base('tenant_lookups')->where('group', 'expense_status')->where(fn ($q) => $q->where('code', $status)->orWhere('name', $status))->first();
        if ($lookup) DB::table('tenant_expenses')->where('id', $expense->id)->update(['status_id' => $lookup->id, 'updated_at' => now()]);
        return $this->success(['expense' => $this->expenseBundle($expense->id), 'status' => $status], 'Expense '.$status.'.');
    }

    private function bankPayload(Request $request, bool $partial = false): array
    {
        $data = $request->only(['owner_type', 'owner_uuid', 'bank_name', 'account_number', 'routing_number', 'ifsc_code', 'is_primary']);
        if (array_key_exists('owner_uuid', $data)) {
            $table = match ($data['owner_type'] ?? 'tenant') { 'client', 'vendor' => 'parties', 'staff' => 'staff', default => null };
            $data['owner_id'] = $table ? $this->idFrom($table, $data['owner_uuid']) : $this->tenantId();
            unset($data['owner_uuid']);
        }
        if (array_key_exists('account_number', $data)) { $data['account_number_encrypted'] = Crypt::encryptString((string) $data['account_number']); unset($data['account_number']); }
        if (array_key_exists('routing_number', $data)) { $data['routing_number_encrypted'] = $data['routing_number'] ? Crypt::encryptString((string) $data['routing_number']) : null; unset($data['routing_number']); }
        if (! $partial) $data += ['owner_type' => 'tenant', 'owner_id' => $this->tenantId(), 'account_number_encrypted' => Crypt::encryptString('')];
        return array_filter($data, fn ($value) => $value !== null && $value !== '');
    }

    private function bankAccount(int $id): array
    {
        $row = DB::table('bank_accounts')->where('tenant_id', $this->tenantId())->where('id', $id)->first();
        abort_if(! $row, 404);
        return $this->tenant->bankPayload($row);
    }

    private function clearOtherPrimary(int $id, string $ownerType, int $ownerId): void { DB::table('bank_accounts')->where('tenant_id', $this->tenantId())->where('owner_type', $ownerType)->where('owner_id', $ownerId)->where('id', '!=', $id)->update(['is_primary' => false]); }
    private function filePayload(object $file): array { return [...(array) $file, 'size_label' => number_format(((int) $file->size_bytes) / 1024, 1).' KB']; }
    private function folderFiles(int $folderId) { return DB::table('document_folder_files')->join('files', 'files.id', '=', 'document_folder_files.file_id')->where('document_folder_files.tenant_id', $this->tenantId())->where('document_folder_id', $folderId)->select('files.uuid', 'files.original_name', 'files.mime_type', 'files.size_bytes', 'document_folder_files.created_at')->get(); }
    private function reportCodes(): array { return ['crm-summary' => 'CRM Summary', 'hr-summary' => 'HR Summary', 'payroll-summary' => 'Payroll Summary', 'renewal-summary' => 'Renewal Summary', 'finance-summary' => 'Finance Summary', 'project-summary' => 'Project Summary', 'task-summary' => 'Task Summary', 'support-summary' => 'Support Summary']; }
    private function reportRows(string $code, Request $request) { return match ($code) { 'crm-summary' => $this->base('parties')->selectRaw('party_type, count(*) as total')->groupBy('party_type')->get(), 'hr-summary' => $this->base('staff')->selectRaw('employment_status, count(*) as total')->groupBy('employment_status')->get(), 'payroll-summary' => $this->base('payrolls')->selectRaw('payment_status, sum(net_salary) as amount, count(*) as total')->groupBy('payment_status')->get(), 'renewal-summary' => $this->base('renewals')->leftJoin('tenant_lookups as status', 'status.id', '=', 'renewals.status_id')->selectRaw('coalesce(status.name, ?) as status, count(*) as total', ['Unassigned'])->groupBy('status.name')->get(), 'finance-summary' => $this->base('tenant_invoices')->selectRaw('status, sum(total_amount) as amount, sum(balance_amount) as balance, count(*) as total')->groupBy('status')->get(), 'project-summary' => $this->base('projects')->leftJoin('tenant_lookups as status', 'status.id', '=', 'projects.status_id')->selectRaw('coalesce(status.name, ?) as status, count(*) as total', ['Unassigned'])->groupBy('status.name')->get(), 'task-summary' => $this->base('tasks')->leftJoin('tenant_lookups as status', 'status.id', '=', 'tasks.status_id')->selectRaw('coalesce(status.name, ?) as status, count(*) as total', ['Unassigned'])->groupBy('status.name')->get(), 'support-summary' => $this->base('client_issues')->leftJoin('tenant_lookups as status', 'status.id', '=', 'client_issues.status_id')->selectRaw('coalesce(status.name, ?) as status, count(*) as total', ['Unassigned'])->groupBy('status.name')->get(), default => [] }; }
    private function settingsRows(string $group) { return $this->base('tenant_settings')->where('group', $group)->orderBy('key')->get()->map(fn ($row) => tap($row, fn ($item) => $item->value_display = $this->displayJson($item->value))); }
    private function displayJson(mixed $value): mixed { $decoded = json_decode((string) $value, true); return is_array($decoded) ? collect($decoded)->map(fn ($item, $key) => is_scalar($item) ? "{$key}: {$item}" : "{$key}: configured")->implode(', ') : $decoded; }
    private function logCommunication(Request $request, string $channel, string $direction, string $subject, string $body, string $status, ?int $partyId = null): int { return DB::table('communication_logs')->insertGetId(['uuid' => (string) Str::uuid(), 'tenant_id' => $this->tenantId(), 'user_id' => $request->user()?->id, 'party_id' => $partyId, 'channel' => $channel, 'direction' => $direction, 'subject' => $subject, 'body' => $body, 'provider' => 'manual', 'status' => $status, 'created_at' => now()]); }
    private function integrationRows() { return $this->base('tenant_integrations')->join('integration_providers', 'integration_providers.id', '=', 'tenant_integrations.provider_id')->select('tenant_integrations.*', 'integration_providers.name as provider_name', 'integration_providers.code as provider_code', 'integration_providers.category', 'integration_providers.auth_type'); }
    private function integrationBundle(int $id): array { return ['record' => $this->integrationRows()->where('tenant_integrations.id', $id)->first(), 'credentials' => DB::table('integration_credentials')->where('tenant_integration_id', $id)->get(['key', 'expires_at']), 'webhooks' => DB::table('integration_webhooks')->where('tenant_integration_id', $id)->get(), 'sync_jobs' => DB::table('integration_sync_jobs')->where('tenant_integration_id', $id)->orderByDesc('id')->limit(25)->get(), 'mappings' => DB::table('integration_field_mappings')->where('tenant_integration_id', $id)->get(), 'rate_limits' => DB::table('integration_rate_limits')->where('tenant_integration_id', $id)->orderByDesc('id')->limit(25)->get()]; }
    private function storeCredentials(int $integrationId, array $credentials): void { foreach ($credentials as $key => $value) DB::table('integration_credentials')->updateOrInsert(['tenant_integration_id' => $integrationId, 'key' => $key], ['encrypted_value' => Crypt::encryptString((string) $value), 'expires_at' => null]); }
    private function integrationChildIndex(Request $request, string $table, string $key): JsonResponse { $ids = $this->base('tenant_integrations')->pluck('id'); $page = DB::table($table)->whereIn('tenant_integration_id', $ids)->orderByDesc('id')->paginate($request->integer('per_page', 25)); return $this->list($page->items(), $page); }
}
