# Database-Programming-2

Recipe sharing web application for the IT8415 Database Programming 2 group project.

## Clean Project Structure

The duplicated `PhpProject2/htdocs` upload folder has been merged into the main `PhpProject2` folder and removed.

Important folders:

- `PhpProject2/actions` - form/AJAX actions such as rating and comments.
- `PhpProject2/admin` - administrator dashboard and reports.
- `PhpProject2/assets` - CSS and JavaScript.
- `PhpProject2/creator` - creator pages for adding and viewing own recipes.
- `PhpProject2/includes` - shared database connection, header, and footer.
- `PhpProject2/uploads` - should exist on the server for uploaded images/videos.

Database connection:

- Edit `PhpProject2/includes/db_connect.php`.
- Current local defaults are `localhost`, `root`, empty password, and database `dbProj_recipe_db`.
- The PhpStorm SFTP settings point the local project to `/public_html` on `http://20.74.143.233/~u202303672/`.

## SQL Schema Used By Code

The PHP code now matches the table and column names from `sql codes.txt`:

- `dbProj_User`
- `dbProj_Recipes`
- `dbProj_Comments`
- `dbProj_Ratings`
- User role column: `Role`
- Recipe image column: `ImagePath`
- Recipe description column: `Description`

Old names such as `dbProj_users`, `dbProj_recipes`, `RoleID`, `ShortDescription`, and `MainImage` were removed from the PHP code.

## Project Task Status

| Task | Requirement | Status | Notes |
| --- | --- | --- | --- |
| 1.1 | Sign-up functionality | Finished | `register.php` inserts into `dbProj_User`. |
| 1.1 | Login functionality | Finished | `login.php` verifies password hashes and redirects by role. |
| 1.1 | Session management | Finished | Shared session logic is in `includes/header.php`. |
| 1.1 | JavaScript validation | Finished | Registration includes browser-side validation. |
| 1.1 | Server-side validation | Finished | Login, registration, recipe creation, comments, and ratings validate inputs. |
| 1.1 | Encrypted passwords | Finished | `password_hash()` and `password_verify()` are used. |
| 1.1 | Three user roles: Viewer, Creator, Admin | Finished | Code uses the `Role` enum from the SQL file. |
| 1.2 | Main page content listing | Finished | `index.php` lists published recipes newest first. |
| 1.2 | Search functionality | Finished | Search supports title/ingredient/description, creator, date range, and popularity sort. |
| 1.2 | Recipe cards with image, text, and link | Finished | Cards use `ImagePath`, `Description`, and link to `recipe_view.php`. |
| 1.2 | Media upload/display | Finished | Creator form supports image and video upload; recipe page displays video if present. |
| 1.4 | Rating system | Finished | AJAX star rating uses `dbProj_Ratings`. |
| 1.4 | Comments | Finished | Logged-in users can post comments and viewers can see them. |
| 1.5 | Creator panel | Finished | `creator/add_recipe.php` and `creator/my_recipes.php` are present. |
| 1.6 | Admin panel | Partly finished | `admin/index.php` shows users and reports, but full delete/manage workflows are still needed. |
| 1.7 | Reports | Partly finished | Popular recipes and content-by-creator reports are started. |
| 2 | Professional responsive UI | Finished | Bootstrap plus project CSS are used. |
| 2 | Consistent navigation | Finished | Shared header handles root, admin, and creator paths. |
| 2 | Pagination if content > 10 records | Not finished | Recipe listing still needs pagination. |
| 2 | Secure database usage | Finished | Main queries now use prepared statements. |
| 3 | ERD | Not finished | Add ERD screenshot/PDF. |
| 3 | Stored procedure | Finished | `sql codes.txt` contains `sp_SearchRecipes`. |
| 3 | Minimum test dataset | Partly finished | SQL has sample users and recipes, but more records are needed for the required dataset size. |
| 4 | Advanced feature 1 | Finished | Prepared statements are used. |
| 4 | Advanced feature 2 | Finished | AJAX/jQuery rating is implemented. |
| 5 | Test plan document | Not finished | Add screenshots and test steps for every requirement. |
| Deliverable | Testing accounts | Partly finished | SQL lists intended accounts, but verify the inserted password hashes before submission. |
| Deliverable | SQL scripts | Finished | `sql codes.txt` contains the schema and procedure. |

## Main Remaining Work

1. Add pagination to the recipe listing.
2. Complete admin delete/manage workflows for users, content, and inappropriate comments.
3. Add enough seed data to meet the minimum dataset requirement.
4. Add the ERD and final test plan with screenshots.
5. Verify deployment database credentials and testing account passwords on the server.
