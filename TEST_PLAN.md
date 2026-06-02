# RecipeShare Test Plan

Project: RecipeShare - IT8415 Database Programming 2  
Test environment: Browser, deployed PHP site, MySQL database, seeded test data from `sql codes.txt`  
Main roles tested: Guest, Viewer, Creator, Admin

Use this document with screenshots from the deployed system. Replace each screenshot placeholder with the actual image number/name used in the final report.

## Test Accounts

| Role | Username | Purpose |
| --- | --- | --- |
| Admin | `admin_user` | Admin dashboard, reports, user list, creator content report |
| Creator | `chef_mario` | Add, edit, publish, delete own recipes |
| Creator | `baker_amy` | Verify creator ownership restrictions |
| Viewer | registered viewer account | Commenting, rating, viewing public recipes |
| Guest | not logged in | Public browsing and permission checks |

## Feature 1: User Registration

Purpose: Allow new users to create Viewer or Creator accounts with secure password storage.

Steps to test:
1. Open `register.php`.
2. Enter a valid username, valid email, password with at least 6 characters, matching confirmation, and select Viewer.
3. Submit the form.
4. Repeat with Creator selected.
5. Try invalid inputs: short username, invalid email, short password, mismatched confirmation, and duplicate username/email.

Expected output:
The valid account is created and the user is redirected to `login.php`. Invalid inputs show clear error messages and the account is not created.

Screenshot evidence:
`Screenshot 1 - Registration form`  
`Screenshot 2 - Registration validation error`  
`Screenshot 3 - Redirect to login after successful registration`

Validation or error handling shown:
Client-side JavaScript checks username length, password length, and password confirmation. Server-side PHP validates required fields, email format, password length, matching confirmation, allowed role, and duplicate username/email.

## Feature 2: User Login and Session Redirects

Purpose: Authenticate users and send each role to the correct area of the system.

Steps to test:
1. Open `login.php`.
2. Login as Admin.
3. Logout and login as Creator.
4. Logout and login as Viewer.
5. Try an incorrect password and empty fields.

Expected output:
Admin is redirected to `admin/index.php`. Creator is redirected to `creator/my_recipes.php`. Viewer is redirected to `index.php`. Invalid credentials show `Invalid username or password.`

Screenshot evidence:
`Screenshot 4 - Login page`  
`Screenshot 5 - Admin dashboard after login`  
`Screenshot 6 - Creator dashboard after login`  
`Screenshot 7 - Invalid login message`

Validation or error handling shown:
Required username/password fields are enforced. Passwords are verified using `password_verify()` against hashed database passwords. Incorrect credentials do not reveal whether the username or password was wrong.

## Feature 3: Public Recipe Browsing

Purpose: Let guests and logged-in users view published recipes.

Steps to test:
1. Open `index.php` while logged out.
2. Confirm the Latest Recipes section appears.
3. Confirm All Recipes displays recipe cards with image, title, creator, category, views, rating, and View button.
4. Open a recipe using the View button.

Expected output:
Only published recipes are visible on the public listing. Recipe details show title, creator, views, category, status, image/video when available, description, ingredients, instructions, comments, and rating summary.

Screenshot evidence:
`Screenshot 8 - Home page recipe listing`  
`Screenshot 9 - Recipe details page`

Validation or error handling shown:
Recipe output uses `htmlspecialchars()` to prevent unsafe content display. Missing recipe IDs redirect to the home page. Invalid recipe IDs show `Recipe not found.`

## Feature 4: Recipe Search, Filtering, Sorting, and Pagination

Purpose: Help users find recipes by title/ingredient/description, creator, category, date, popularity, and rating.

Steps to test:
1. Search for a known recipe title or ingredient.
2. Filter by creator name.
3. Filter by category.
4. Filter using the Published Since date field.
5. Click Most Popular.
6. Click Top Rated.
7. Navigate through pagination links when enough records exist.
8. Search for a term with no matching recipe.

Expected output:
Matching recipes appear without reloading the whole page. Popular sorting orders by view count. Top Rated sorting orders by average rating. Pagination changes the recipe list. No matches show `No recipes found.`

Screenshot evidence:
`Screenshot 10 - Search results`  
`Screenshot 11 - Category/date filtered results`  
`Screenshot 12 - Top Rated or Most Popular sorting`  
`Screenshot 13 - Pagination`

Validation or error handling shown:
Search inputs are trimmed and used in prepared SQL statements. AJAX failure shows `Unable to load recipes.` Empty result sets show a friendly message.

## Feature 5: Recipe View Count

Purpose: Track recipe popularity for user feedback and admin reports.

Steps to test:
1. Note a recipe's current view count on the card or admin report.
2. Open the recipe detail page.
3. Return to the listing or report and refresh.

Expected output:
The recipe's view count increases by 1 after opening the detail page.

Screenshot evidence:
`Screenshot 14 - View count before opening recipe`  
`Screenshot 15 - View count after opening recipe`

Validation or error handling shown:
The recipe ID is cast to an integer before the update query runs.

## Feature 6: Rating System

Purpose: Allow logged-in users to rate recipes from 1 to 5 stars and update their rating later.

Steps to test:
1. Login as Viewer or Creator.
2. Open a published recipe.
3. Click a star rating.
4. Click a different star rating for the same recipe.
5. Logout and open the same recipe.
6. Send an invalid rating value, such as 0 or 6, using browser developer tools or an API client.

Expected output:
The rating is saved and the message `Rating saved.` appears. Updating the rating changes the same user's existing rating instead of creating duplicates. Logged-out users see a login prompt. Invalid ratings return `Invalid rating.`

Screenshot evidence:
`Screenshot 16 - Star rating before selection`  
`Screenshot 17 - Rating saved message`  
`Screenshot 18 - Login prompt for logged-out user`

Validation or error handling shown:
The rating endpoint requires a session user, checks recipe ID, checks rating range 1-5, and uses a prepared statement with `ON DUPLICATE KEY UPDATE`.

## Feature 7: Comments

Purpose: Allow logged-in users to discuss recipes.

Steps to test:
1. Login as Viewer.
2. Open a recipe detail page.
3. Type a valid comment and submit.
4. Refresh the recipe page.
5. Try submitting an empty comment.
6. Logout and open the recipe page.

Expected output:
Valid comments appear under the recipe with username and timestamp. Empty comments are not inserted. Logged-out users can read comments but cannot see the comment form.

Screenshot evidence:
`Screenshot 19 - Comment form`  
`Screenshot 20 - Posted comment displayed`  
`Screenshot 21 - Logged-out view without comment form`

Validation or error handling shown:
The comment action requires login, trims comment text, checks recipe ID, ignores empty comments, and escapes comment output.

## Feature 8: Creator Add Recipe

Purpose: Let Creator users add draft or published recipes with optional image/video media.

Steps to test:
1. Login as `chef_mario`.
2. Open `creator/add_recipe.php`.
3. Submit a recipe with title, description, category, status, and valid image/video files.
4. Submit another recipe without optional ingredients, instructions, image, or video.
5. Try submitting with missing title, missing description, invalid category, or invalid status.

Expected output:
Valid recipes are saved and show `Recipe saved successfully.` Missing optional fields are accepted. Invalid required fields show a clear error and the recipe is not saved.

Screenshot evidence:
`Screenshot 22 - Add recipe form`  
`Screenshot 23 - Recipe saved successfully`  
`Screenshot 24 - Add recipe validation error`

Validation or error handling shown:
Only Creators can access the page. Required fields are checked server-side. Category and status must be in allowed lists. Uploaded filenames are sanitized and only allowed image/video extensions are accepted.

## Feature 9: Creator My Recipes Dashboard

Purpose: Let Creators manage their own recipe content.

Steps to test:
1. Login as `chef_mario`.
2. Open `creator/my_recipes.php`.
3. Confirm only Mario's recipes are listed.
4. Confirm each row shows title, category, status, views, created date, and View/Edit/Delete actions.

Expected output:
The Creator sees only their own recipes, ordered by newest first. If they have no recipes, the page shows `You have not created any recipes yet.`

Screenshot evidence:
`Screenshot 25 - Creator My Recipes dashboard`

Validation or error handling shown:
The query filters by the logged-in `UserID`. Non-Creator users are redirected to login.

## Feature 10: Edit Recipe and Ownership Protection

Purpose: Allow Creators to update their own recipes while preventing access to other Creators' content.

Steps to test:
1. Login as `chef_mario`.
2. Edit one of Mario's recipes.
3. Change title, category, status, image, or video and save.
4. Try opening an edit URL for a recipe owned by `baker_amy`.
5. Try invalid category/status values using browser developer tools.

Expected output:
Valid edits show `Recipe updated successfully.` The changed fields appear after saving. Editing another Creator's recipe shows `Recipe not found or access denied.` Invalid category/status values show errors.

Screenshot evidence:
`Screenshot 26 - Edit recipe form`  
`Screenshot 27 - Recipe updated successfully`  
`Screenshot 28 - Access denied when editing another creator recipe`

Validation or error handling shown:
The recipe is loaded using both `RecipeID` and logged-in `UserID`. Updates also use both IDs in the `WHERE` clause. File uploads are extension-checked and filenames are sanitized.

## Feature 11: Delete Recipe

Purpose: Allow Creators to remove their own recipes and associated upload files.

Steps to test:
1. Login as Creator.
2. Open My Recipes.
3. Click Delete for one owned recipe and confirm the browser prompt.
4. Verify the recipe is removed from My Recipes and public listings.
5. Try deleting another Creator's recipe by manually changing the `id` in the URL.

Expected output:
Owned recipes are deleted and the user returns to My Recipes. Recipes owned by another user are not deleted.

Screenshot evidence:
`Screenshot 29 - Delete confirmation`  
`Screenshot 30 - Recipe removed from My Recipes`

Validation or error handling shown:
The delete query filters by `RecipeID` and logged-in `UserID`. Non-Creator users are redirected to login.

## Feature 12: Recipe Status Management

Purpose: Let Creators and Admins control whether a recipe is Draft or Published.

Steps to test:
1. Login as Creator and open one of your recipes.
2. Change the status to Draft and submit.
3. Confirm it no longer appears on the public home page.
4. Change the status back to Published.
5. Login as Admin and update a recipe status.
6. Try sending an invalid status value.

Expected output:
Status updates show `Status updated to Draft.` or `Status updated to Published.` Draft recipes are hidden from public listings. Invalid statuses are ignored.

Screenshot evidence:
`Screenshot 31 - Status update control`  
`Screenshot 32 - Status updated message`  
`Screenshot 33 - Draft recipe hidden from home page`

Validation or error handling shown:
Only the owner or Admin can update status. Status must be `Draft` or `Published`.

## Feature 13: Admin Dashboard and User List

Purpose: Give Admin users a controlled area to review users and system reports.

Steps to test:
1. Login as Admin.
2. Open `admin/index.php`.
3. Confirm the Users table lists ID, username, email, role, and created date.
4. Logout and try opening `admin/index.php` as Guest, Viewer, and Creator.

Expected output:
Admin can view the dashboard. Non-Admin users are redirected to login and cannot access the dashboard content.

Screenshot evidence:
`Screenshot 34 - Admin dashboard user list`  
`Screenshot 35 - Non-admin redirected away from admin dashboard`

Validation or error handling shown:
Admin page checks `$_SESSION['role'] === 'Admin'` before loading reports. Output is escaped with `htmlspecialchars()`.

## Feature 14: Admin Reports

Purpose: Prove the system produces correct reports for recipe activity and content management.

Steps to test:
1. Login as Admin.
2. Open the Most Popular report.
3. Open the Top Ranked report.
4. Open the Latest Published report.
5. Compare each report to database records or visible recipe data.

Expected output:
Most Popular lists recipes ordered by highest views. Top Ranked lists recipes ordered by average rating and shows `No ratings` when needed. Latest Published lists recently published recipes by created date.

Screenshot evidence:
`Screenshot 36 - Most Popular report`  
`Screenshot 37 - Top Ranked report`  
`Screenshot 38 - Latest Published report`

Validation or error handling shown:
Reports use prepared statements and aggregate SQL (`AVG`, `COUNT`, `COALESCE`) to handle recipes with and without ratings.

## Feature 15: Content By Creator Report and Creator Autocomplete

Purpose: Let Admin search for a Creator and view all content created by that user.

Steps to test:
1. Login as Admin.
2. In Content By Creator, type part of a Creator username.
3. Select a Creator from autocomplete results.
4. Confirm the table loads that Creator's recipes.
5. Search for a name that does not exist.
6. Try calling `actions/search_creators.php` as a non-Admin user.

Expected output:
Autocomplete returns Creator users only. Selecting a Creator loads recipe, category, status, views, and created date. Unknown creators show `No creators found` or `No creator content found.` Non-Admin access receives HTTP 403 and an empty JSON array.

Screenshot evidence:
`Screenshot 39 - Creator autocomplete results`  
`Screenshot 40 - Content By Creator report`  
`Screenshot 41 - No creator found message`  
`Screenshot 42 - Non-admin 403 response`

Validation or error handling shown:
The search and fetch endpoints require Admin role. Empty terms return an empty array. Creator IDs are cast to integers. Queries use prepared statements.

## Feature 16: Role Permissions

Purpose: Prove that the system enforces Guest, Viewer, Creator, and Admin permissions.

Steps to test:
1. As Guest, try to rate, comment, access Creator pages, and access Admin pages.
2. As Viewer, try to access Creator pages and Admin pages.
3. As Creator, access Creator pages and try Admin pages.
4. As Admin, access Admin pages and update recipe status.

Expected output:
Guest can browse published recipes only. Viewer can browse, comment, and rate. Creator can manage own recipes. Admin can access reports and status controls. Unauthorized page access redirects to login or returns 403 for protected AJAX endpoints.

Screenshot evidence:
`Screenshot 43 - Viewer blocked from Creator page`  
`Screenshot 44 - Creator blocked from Admin page`  
`Screenshot 45 - AJAX endpoint 403`

Validation or error handling shown:
Session checks use `isLoggedIn()` and `checkRole()`. Actions enforce permissions before database operations.

## Feature 17: Professional Error Pages and Database Connection Handling

Purpose: Prevent raw database/server errors from appearing to users and show professional HTTP error pages.

Steps to test:
1. Open a URL that does not exist.
2. Open a forbidden directory or protected file path.
3. Temporarily use invalid database credentials in a test environment.
4. Call a JSON/AJAX endpoint while the database is unavailable.

Expected output:
Missing pages show a styled `404 Page Not Found` page. Forbidden pages show `403 Access Denied`. Database failure shows `503 Service Unavailable` without exposing the MySQL error. JSON/AJAX requests return a clean JSON message.

Screenshot evidence:
`Screenshot 46 - Custom 404 page`  
`Screenshot 47 - Custom 403 page`  
`Screenshot 48 - Custom 503 database unavailable page`

Validation or error handling shown:
`.htaccess` maps common HTTP errors to custom pages. `db_connect.php` disables public error display, logs database errors, sends HTTP 503, and returns either HTML or JSON depending on request type.

## Feature 18: Advanced Feature - AJAX Interaction

Purpose: Prove advanced client-side/server-side functionality works without full page reloads.

Steps to test:
1. Search or paginate recipes on the home page.
2. Rate a recipe with stars.
3. Use Admin Creator autocomplete.

Expected output:
Recipe results update inside the page. Rating saves and updates the star state. Creator autocomplete displays matching creators and loads the report table dynamically.

Screenshot evidence:
`Screenshot 49 - AJAX recipe results update`  
`Screenshot 50 - AJAX star rating saved`  
`Screenshot 51 - AJAX creator report loaded`

Validation or error handling shown:
AJAX failures display user-friendly messages such as `Unable to load recipes`, `Error connecting to server`, or hide invalid autocomplete results.

## Feature 19: Advanced Feature - Secure Database Queries

Purpose: Prove the system protects database access by using prepared statements.

Steps to test:
1. Search for a normal recipe term.
2. Search using SQL-like input such as `' OR '1'='1`.
3. Try SQL-like input in login username, creator search, and recipe filters.

Expected output:
The system treats the input as text. It does not expose SQL errors, bypass login, or return unintended records.

Screenshot evidence:
`Screenshot 52 - SQL-like search input handled safely`  
`Screenshot 53 - SQL-like login input rejected`

Validation or error handling shown:
Main queries use `$mysqli->prepare()` and `bind_param()`. User output is escaped before display.

## Feature 20: Advanced Database Features - Stored Procedure and Trigger

Purpose: Prove the SQL deliverable includes advanced database objects.

Steps to test:
1. Run `CALL sp_GetPopularRecipes();` in MySQL.
2. Confirm it returns recipes ordered by views.
3. Review the trigger definition in `sql codes.txt`.

Expected output:
The stored procedure returns the popular recipe report. The trigger exists in the SQL script as advanced database logic.

Screenshot evidence:
`Screenshot 54 - Stored procedure output`  
`Screenshot 55 - Trigger definition in database or SQL script`

Validation or error handling shown:
The stored procedure joins recipes with users and limits output. Foreign keys use cascade rules to keep related data consistent.

## Final Pass Criteria

The system passes testing when:

| Requirement | Pass condition |
| --- | --- |
| Works correctly | All public, Viewer, Creator, and Admin workflows complete successfully. |
| Handles invalid input properly | Invalid forms, invalid ratings, invalid roles, invalid recipe IDs, and duplicate accounts show controlled behavior. |
| Enforces role permissions | Guest, Viewer, Creator, and Admin access boundaries cannot be bypassed through direct URLs or AJAX calls. |
| Produces correct reports | Admin reports match database values for views, ratings, latest published recipes, users, and creator content. |
| Implements advanced features successfully | AJAX, prepared statements, password hashing, stored procedure, trigger, pagination, media upload, and custom error pages are demonstrated with screenshots. |

