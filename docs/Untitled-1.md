php artisan queue:work

In phase 1:

in database seeders create following type of data in tables:

first in platform:

1. create all permissions records
2. create roles records. permissions need to attached with each roles.
3. create platform users with their full information.
4. create team roles also make sure they have their permissions attached.
5. create teams with attached team members and assignments.

also there are 15-23 records needed. and all tables have activity logs.

In phase 2:

in database seeders create following type of data in tables:

In platform:

1. create add ons records.
2. create features records.
3. create plans records.

also there are 15-23 records needed. and all tables have activity logs.

In phase 3:

in database seeders create following type of data in tables:

In platform:

1. create records for modules table. as in this project modules are fixed in future if we add any module then we can add records in this. so now check and add current available all modules list.
2. create records for settings table for platform super admin. In this also settings are fixed settings so make sure these records are original and exists.
3. create records for integrations table for platform super admin. In this also use original and exists data. create templates also.

In platform teams module check all following pages:

In all these pages check if the data is displayed is required to show or not. just like the previous.
Also check if the ids input is getting for relationship or dropdowns as we needed dropdowns. and all forms showing proper errors for the same input or not. and don't show error on the top just for that input is enough.
Also check data representation is perfect. like dates will be actual date format, names will be in title case, etc.
Remove unused things also.

In platform staff module check all following pages:

http://localhost:5173/platform/staff
http://localhost:5173/platform/staff/create
http://localhost:5173/platform/staff/067ad226-ff45-471e-8b1b-2b3f353ac602
http://localhost:5173/platform/staff/067ad226-ff45-471e-8b1b-2b3f353ac602/edit

first check missing data in these all pages. as in some page we are using data and in some not. as we don't want that type. if the data is required then use that properly.

In all these pages check if the data is displayed is required to show or not. just like the previous.
Also check if the ids input is getting for relationship or dropdowns as we needed dropdowns. and all forms showing proper errors for the same input or not. and don't show error on the top just for that input is enough.
Also check data representation is perfect. like dates will be actual date format, names will be in title case, etc.
Remove unused things also.

in list for action button popup check all buttons are working properly. not just opening popup those popups also need to work correctly. as their functionalities with api work correctly.

in following page add \* required for those inputs which are required.

http://localhost:5173/platform/catalog/add-ons
http://localhost:5173/platform/catalog/add-ons/create
http://localhost:5173/platform/catalog/add-ons/2a0ab42f-7f06-4b2c-8252-50fa427424a2/edit
http://localhost:5173/platform/catalog/add-ons/2a0ab42f-7f06-4b2c-8252-50fa427424a2

check create and edit popup

strictly check in backend first for which fields are mandatory then proceed to implement.
so user will understand which fields are mandatory and which not.
after making changes recheck to improve.

Use Playwright MCP to open my running React application,
inspect the rendered UI, identify layout and responsive issues,
and fix them in the source code.

Use Playwright MCP and inspect http://localhost:5173/auth/login.
Check the page at desktop and mobile widths.
Find UI problems and fix them in the React code.
After each major change, verify the result again with Playwright.

====================

- apis needed

delete add on api - add on
delete and archive - feature
add feature form correction - plans list
clone plan form correction - plan list
delete and archive - plan

coupons create form correction - coupons list
coupons popup forms corrections - coupons list and view page.
