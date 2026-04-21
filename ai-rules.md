# Project Coding Rules (PMS - Production Management System)

## 1. Project Structure
- **React Components**: `resources/js/Components`
- **React Pages (Inertia)**: `resources/js/Pages`
- **Classic Views**: `resources/views/pages`
- **Controllers**: `app/Http/Controllers/Pages`
- **Models**: `app/Models`
- **Assets**: `public/js`, `public/css`

## 2. Naming Conventions
- **React Components**: PascalCase (e.g., `MaintenanceCalendar.jsx`)
- **React Hooks**: camelCase starting with `use` (e.g., `useAssignment.js`)
- **API Functions**: camelCase (e.g., `getUserList`, `saveAssignment`)
- **Database Fields**: snake_case (e.g., `start_time`, `job_description`)
- **Blade Files**: camelCase or snake_case (standard: `dataTable.blade.php`)
- **Variable names (PHP/JS)**: camelCase

## 3. API & Data Pattern
- **Library**: Use `axios` for all asynchronous requests.
- **Inertia**: Use `router.post()` or `router.get()` for navigation-driven actions in React.
- **Classic**: Use `$.ajax` or `axios` in jQuery scripts.
- **Standard Return Format**:
  ```json
  {
    "success": boolean,
    "data": any,
    "message": string
  }
  ```

## 4. UI Frameworks & UX
- **React Part**: Use **React Bootstrap** for layouts and **PrimeReact** (DataTable, etc.) for complex data components.
- **Classic Part**: Use Standard **Bootstrap 4**, **jQuery**, and **Select2**.
- **Icons**: FontAwesome 5/6.
- **Notifications**: Always use **SweetAlert2 (Swal)** for toasts and confirmation dialogs.
- **Design Style**: Follow the "Stella Gold" theme (Primary Color: `#CDC717`, Secondary: `#003A4F`).

## 5. Coding Logic
- **Separation of Concerns**: Keep UI components clean; move complex business logic to Custom Hooks (React) or Helper classes (PHP).
- **Date Handling**: Use **Carbon** in PHP and **dayjs** or `Date` objects in JS. Format for DB: `YYYY-MM-DD HH:mm:ss`.
- **Validation**: Always validate data on the Backend using Laravel Request Validation.

## 6. Error Handling
- **Async/API**: Always wrap API calls in `try/catch` blocks.
- **Backend**: Use `try/catch` for database transactions and return meaningful error messages.
- **Feedback**: Show toast messages for success/error if no redirect is involved.

## 7. Performance & Integrity
- **Database**: Use Eloquent relationships where possible. Avoid N+1 query problems.
- **Data Integrity**: Implement strict permission checks at the top of Views and inside Controllers (`user_has_permission`).
- **Locks**: Disable editing/saving for historical data (past dates) to maintain records integrity.
