Corrected sequence:

1. Flowchart of user flow
2. DB diagram (tables, fields, relationships)
3. Enums + DTOs for all fixed/constant data identified in the diagram
4. composer create-project
5. Decide auth strategy (Sanctum/session, policies/gates) — no code yet, just decision
6. Migrations + model + seeder + factory (single command per model, parent tables before child tables)
7. Update models: $table (only if non-conventional), $fillable/$guarded, $casts (using enums from step 3), relationships
8. Seeder/factory dummy data
9. Routes, controllers, FormRequests, service/repository classes (service class mandatory for any external integration, not optional)
10. API Resource classes + API responses, or view files for web responses
11. Third-party integrations: credentials in .env, wrapped in service classes, dispatched via queued jobs where the call is slow (SMS/email/WhatsApp)
12. Feature tests for critical controller paths



- Today's Goal:
1. migration files       - done
2. seeders & factories       - done
3. modal's code with relationships
4. required packages installation 
5. flow of making apis for all apis
6. starting of building apis 
7. integrating into frontend. 
8. final testing. 


check all model files and add proper following things:

1. $table: add $table value in model file
2. $fillable values and not $guarded: add only $fillable values
3. $casts where required: check for all columns and where required casts those columns
4. accessors and mutators: check where we can use accessors and mutators and implement those
5. relationships: check for every model and add proper relationship. 


as defined in phase 2 migration file add seeder data as a proper with original values for following table and it's relational tables and pivot also:

1. modules table
2. plan table 
3. features table
4. addons table 
5. coupons table 