# Implementation Plan: Nursing Journal Application

## Overview

This implementation plan breaks down the Nursing Journal Application into discrete, incremental coding tasks that build upon each other. The system is built with Laravel 10, MySQL, Tailwind CSS, shadcn/ui, and Recharts. Each task focuses on writing, modifying, or testing code with clear dependencies and acceptance criteria. The implementation follows a layered approach: database foundation → authentication → core features → reporting → security hardening.

## Tasks

- [x] 1. Set up project structure, database migrations, and core models
  - [x] 1.1 Create database migrations for users, units, patient_data, sessions, and login_attempts tables
    - Write migrations with proper indexes, foreign keys, and constraints
    - Ensure UNIQUE constraints on username and unit names
    - _Requirements: 1.5, 6.9, 7.4_
  
  - [x] 1.2 Create Eloquent models (User, Unit, PatientData) with relationships
    - Define model relationships and fillable attributes
    - Implement helper methods (isAdmin, isNurse, getFieldDefinition)
    - _Requirements: 1.5, 2.1, 2.8_
  
  - [x] 1.3 Create database seeders for initial units and test users
    - Seed 6 units (IGD, Rawat Inap, Rawat Jalan, VK, ICU, HCU)
    - Seed test admin and nurse users
    - _Requirements: 6.1, 7.1_
  
  - [ ]* 1.4 Write property tests for database schema integrity
    - **Property 4: Session Contains Required User Data**
    - **Property 43: User Has Exactly One Role**
    - **Validates: Requirements 1.5, 7.5**

- [x] 2. Implement authentication system with login, logout, and session management
  - [x] 2.1 Create AuthController with login and logout methods
    - Implement login validation and credential checking
    - Implement session creation with user data (user_id, role, unit_id)
    - Implement logout with session clearing
    - _Requirements: 1.1, 1.5, 1.6_
  
  - [x] 2.2 Implement rate limiting for failed login attempts
    - Track failed login attempts in login_attempts table
    - Block account after 5 failed attempts within 15 minutes
    - Display account locked message with unlock time
    - _Requirements: 1.3_
  
  - [x] 2.3 Implement session timeout middleware (60-minute inactivity)
    - Create middleware to check last_activity timestamp
    - Automatically logout on timeout
    - Redirect to login with session expired message
    - _Requirements: 1.4_
  
  - [x] 2.4 Create login and logout views with Blade templates
    - Build login form with username and password fields
    - Add error message display for invalid credentials
    - Add account locked message display
    - _Requirements: 1.1, 1.2, 1.3_
  
  - [ ]* 2.5 Write property tests for authentication system
    - **Property 1: Invalid Credentials Return Generic Error**
    - **Property 2: Rate Limiting Blocks After Failed Attempts**
    - **Property 3: Session Timeout After Inactivity**
    - **Property 5: Logout Clears Session Data**
    - **Validates: Requirements 1.2, 1.3, 1.4, 1.6**

- [x] 3. Implement shift detection service and form infrastructure
  - [x] 3.1 Create ShiftDetectionService with getCurrentShift() method
    - Implement shift logic: Pagi (07:00-13:59), Siang (14:00-20:59), Malam (21:00-06:59)
    - Use Carbon with Asia/Jakarta timezone
    - _Requirements: 3.1, 3.2, 3.3_
  
  - [x] 3.2 Create unit-specific field definitions in Unit model
    - Define field arrays for each unit (IGD, Rawat Inap, Rawat Jalan, VK, ICU, HCU)
    - Include field names, types, and validation rules
    - _Requirements: 2.8_
  
  - [x] 3.3 Create PatientDataController with form display method
    - Implement method to get current shift and user's unit
    - Return unit-specific fields for form rendering
    - _Requirements: 2.1, 3.4, 3.5_
  
  - [ ]* 3.4 Write property tests for shift detection
    - **Property 13: Shift Detection Morning Hours**
    - **Property 14: Shift Detection Afternoon Hours**
    - **Property 15: Shift Detection Night Hours**
    - **Property 16: Shift Dropdown Contains All Options**
    - **Property 17: Shift Resets on New Form Load**
    - **Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5**

- [x] 4. Build patient data input form and validation
  - [x] 4.1 Create patient data form Blade template with dynamic field rendering
    - Render unit-specific fields based on user's assigned unit
    - Include shift dropdown with three options
    - Add submit and clear buttons
    - _Requirements: 2.1, 2.8, 3.4_
  
  - [x] 4.2 Implement client-side validation for form fields
    - Validate required fields are filled
    - Validate numeric fields are 0-9999
    - Display inline error messages without clearing data
    - _Requirements: 2.3_
  
  - [x] 4.3 Implement server-side validation in PatientDataController
    - Validate all required fields present and in correct range
    - Validate shift is one of three valid options
    - Return validation errors with field-level messages
    - _Requirements: 2.2, 2.3_
  
  - [x] 4.4 Implement auto-calculated total fields in form
    - Calculate totals based on unit-specific field definitions
    - Update totals in real-time as user enters data
    - _Requirements: 2.8_
  
  - [ ]* 4.5 Write property tests for form validation
    - **Property 6: Unit-Specific Form Fields Display Correctly**
    - **Property 8: Missing Required Fields Trigger Validation**
    - **Validates: Requirements 2.1, 2.3, 2.8**

- [x] 5. Implement patient data storage and duplicate handling
  - [x] 5.1 Create store method in PatientDataController
    - Save patient data to database with date, shift, unit, user metadata
    - Store data as JSON in data column
    - Calculate and store total_patients
    - _Requirements: 2.2_
  
  - [x] 5.2 Implement duplicate entry detection and confirmation
    - Check for existing entry with same date/shift/unit combination
    - Return confirmation dialog response if duplicate exists
    - Allow user to update or cancel
    - _Requirements: 2.6_
  
  - [x] 5.3 Implement success notification and form clearing
    - Display success message for 3 seconds
    - Clear form fields for next entry
    - _Requirements: 2.4_
  
  - [x] 5.4 Implement error handling and form data preservation
    - Catch connection and server errors
    - Display error message
    - Preserve all entered data in form
    - _Requirements: 2.5_
  
  - [ ]* 5.5 Write property tests for data storage
    - **Property 7: Valid Patient Data Persists to Database**
    - **Property 9: Successful Save Clears Form**
    - **Property 10: Failed Save Preserves Form Data**
    - **Property 11: Duplicate Entry Triggers Confirmation**
    - **Validates: Requirements 2.2, 2.4, 2.5, 2.6**

- [x] 6. Implement text output and copy-to-clipboard functionality
  - [~] 6.1 Create method to generate formatted text output from patient data
    - Format data as readable text with field names and values
    - Include date, shift, and unit information
    - _Requirements: 2.7_
  
  - [~] 6.2 Add copy-to-clipboard button and functionality
    - Implement JavaScript to copy text to clipboard
    - Show feedback when copy is successful
    - _Requirements: 2.7_
  
  - [ ]* 6.3 Write property test for text output generation
    - **Property 12: Saved Data Generates Text Output**
    - **Validates: Requirements 2.7**

- [x] 7. Create dashboard pages for nurses and admins
  - [~] 7.1 Create nurse dashboard view
    - Display assigned unit information
    - Display current shift
    - Add quick access link to patient data form
    - _Requirements: 9.2_
  
  - [~] 7.2 Create admin dashboard view
    - Display total units count
    - Display total active users count
    - Add quick access links to management pages
    - _Requirements: 9.3_
  
  - [~] 7.3 Create DashboardController with role-based logic
    - Return appropriate dashboard based on user role
    - Prevent unauthorized access to admin features
    - _Requirements: 9.1, 9.3, 9.4_
  
  - [ ]* 7.4 Write property tests for dashboard access control
    - **Property 51: Dashboard Shows Role-Appropriate Content**
    - **Property 52: Nurse Dashboard Shows Required Information**
    - **Property 53: Admin Dashboard Shows Required Statistics**
    - **Validates: Requirements 9.1, 9.2, 9.3, 9.4**

- [x] 8. Implement unit management (CRUD operations)
  - [~] 8.1 Create UnitController with index, store, update, destroy methods
    - Implement list all units with status
    - Implement create unit with validation
    - Implement update unit name
    - Implement delete with related data warning
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5, 6.6, 6.7_
  
  - [~] 8.2 Create unit management views (list, create, edit)
    - Build table view with unit names and status
    - Build form for creating new units
    - Build form for editing existing units
    - _Requirements: 6.1, 6.2, 6.4_
  
  - [~] 8.3 Implement unit name validation (2-50 chars, alphanumeric + space)
    - Validate length constraints
    - Validate character restrictions
    - Check for duplicate names (case-insensitive)
    - _Requirements: 6.2, 6.3, 6.8, 6.9_
  
  - [~] 8.4 Implement delete confirmation dialog with data warning
    - Count related patient data records
    - Display warning with count
    - Allow user to confirm or cancel
    - _Requirements: 6.5, 6.6, 6.7_
  
  - [ ]* 8.5 Write property tests for unit management
    - **Property 31: Unit List Displays All Units**
    - **Property 32: Valid Unit Name Saves Successfully**
    - **Property 33: Duplicate Unit Name Rejected**
    - **Property 34: Unit Edit Updates Database**
    - **Property 35: Delete Unit with Data Shows Warning**
    - **Property 36: Confirmed Unit Delete Removes Record**
    - **Property 37: Cancelled Unit Delete Preserves Record**
    - **Property 38: Invalid Unit Name Length Rejected**
    - **Validates: Requirements 6.1, 6.2, 6.3, 6.4, 6.5, 6.6, 6.7, 6.8, 6.9**

- [x] 9. Implement user management (CRUD operations)
  - [~] 9.1 Create UserController with index, store, update, destroy methods
    - Implement list all users with details
    - Implement create user with password hashing
    - Implement update user unit assignment
    - Implement deactivate/activate user
    - _Requirements: 7.1, 7.2, 7.4, 7.6, 7.7, 7.8_
  
  - [~] 9.2 Create user management views (list, create, edit)
    - Build table view with username, full name, unit, status
    - Build form for creating new users
    - Build form for editing user details
    - _Requirements: 7.1, 7.2, 7.6_
  
  - [~] 9.3 Implement user validation (username unique, password ≥8 chars)
    - Validate username uniqueness
    - Validate password minimum length
    - Validate full name and unit assignment
    - _Requirements: 7.2, 7.4_
  
  - [~] 9.4 Implement password hashing with bcrypt
    - Hash passwords before storing
    - Never display passwords in plaintext
    - _Requirements: 7.9, 10.4_
  
  - [~] 9.5 Implement user deactivation and session clearing
    - Deactivate user account
    - Clear all active sessions for deactivated user
    - Prevent login for deactivated users
    - _Requirements: 7.7_
  
  - [~] 9.6 Implement user reactivation
    - Allow reactivation of deactivated accounts
    - Enable login after reactivation
    - _Requirements: 7.8_
  
  - [ ]* 9.7 Write property tests for user management
    - **Property 39: User List Displays All Users**
    - **Property 40: Valid User Data Saves Successfully**
    - **Property 41: User Creation Failure Shows Generic Error**
    - **Property 42: Duplicate Username Rejected**
    - **Property 44: User Unit Assignment Updates**
    - **Property 45: Deactivated User Cannot Login**
    - **Property 46: Reactivated User Can Login**
    - **Property 47: Password Stored as Hash**
    - **Validates: Requirements 7.1, 7.2, 7.3, 7.4, 7.6, 7.7, 7.8, 7.9**

- [x] 10. Checkpoint - Ensure all authentication and management tests pass
  - Ensure all tests pass for authentication, shift detection, patient data, units, and users
  - Ask the user if questions arise.

- [x] 11. Implement report data retrieval and filtering
  - [~] 11.1 Create ReportController with data retrieval method
    - Implement filtering by unit, shift, and date range
    - Validate date range (max 90 days, start ≤ end)
    - Return data in format suitable for chart rendering
    - _Requirements: 4.1, 5.1, 5.2, 5.6, 5.7_
  
  - [~] 11.2 Implement default filter logic (today, all units, all shifts)
    - Set default filters on first report page load
    - Preserve filter state during session
    - Reset to defaults on new session
    - _Requirements: 5.3, 5.4_
  
  - [~] 11.3 Implement date range validation
    - Validate start_date ≤ end_date
    - Validate max 90 days range
    - Return validation error if invalid
    - _Requirements: 5.6_
  
  - [ ]* 11.4 Write property tests for report filtering
    - **Property 18: Report Page Displays Filter Controls**
    - **Property 27: Filter Changes Update Chart Quickly**
    - **Property 28: First Report Load Shows Today's Data**
    - **Property 29: Invalid Date Range Shows Validation Error**
    - **Property 30: Filtered Data Matches All Criteria**
    - **Validates: Requirements 4.1, 5.1, 5.2, 5.3, 5.4, 5.6, 5.7**

- [x] 12. Create report views with filter controls
  - [~] 12.1 Create report page Blade template
    - Build filter section with unit, shift, date range selectors
    - Add responsive chart container
    - Add loading and error message areas
    - _Requirements: 4.1, 5.1_
  
  - [~] 12.2 Implement filter UI components
    - Unit dropdown (single unit or all units)
    - Shift dropdown (single shift or all shifts)
    - Date range picker (start and end dates)
    - Apply filters button
    - _Requirements: 5.1_
  
  - [~] 12.3 Implement client-side filter state management
    - Store filter selections in JavaScript
    - Update chart on filter change
    - Preserve filter state during session
    - _Requirements: 5.2, 5.3_

- [x] 13. Implement line chart rendering with Recharts
  - [~] 13.1 Create line chart component with Recharts
    - Render chart with dates on X-axis, patient counts on Y-axis
    - Support single unit and multi-unit (all units) views
    - Use different colors for each unit
    - _Requirements: 4.2, 4.3, 4.4_
  
  - [~] 13.2 Implement interactive tooltips
    - Display date, unit name, patient count, and shift on hover
    - Format tooltip text clearly
    - _Requirements: 4.6_
  
  - [~] 13.3 Implement responsive chart container
    - Chart adapts to viewport size (320px-1920px)
    - Horizontal scroll on mobile (≤768px)
    - Clear visual indicators for scrollable content
    - _Requirements: 4.5, 8.1, 8.4_
  
  - [~] 13.4 Implement empty state and error handling
    - Display informative message when no data matches filters
    - Display error message if data load fails
    - Hide loading indicator after 5 seconds
    - _Requirements: 4.7, 4.8, 5.5_
  
  - [ ]* 13.5 Write property tests for line chart
    - **Property 19: Line Chart Renders with Correct Axes**
    - **Property 20: Unit Filter Displays Only Selected Unit Data**
    - **Property 21: All Units Selection Shows Multiple Lines**
    - **Property 22: Chart Renders Responsively**
    - **Property 23: Tooltip Displays Complete Information**
    - **Property 24: Empty Data Shows Informative Message**
    - **Property 25: Loading Timeout Displays Error**
    - **Validates: Requirements 4.2, 4.3, 4.4, 4.5, 4.6, 4.7, 4.8, 8.1, 8.4**

- [x] 14. Implement candle chart for monthly reporting
  - [~] 14.1 Create candle chart component with Recharts
    - Render monthly data with open, high, low, close values
    - Display one candle per day
    - Support unit and month selection
    - _Requirements: 4.9_
  
  - [~] 14.2 Implement monthly data aggregation
    - Calculate open (first entry), high (max), low (min), close (last entry) per day
    - Group data by month and unit
    - _Requirements: 4.9_
  
  - [ ]* 14.3 Write property test for candle chart
    - **Property 26: Candle Chart Renders Monthly Data**
    - **Validates: Requirements 4.9**

- [x] 15. Implement responsive design for mobile and tablet
  - [~] 15.1 Create responsive layout with Tailwind CSS
    - Implement mobile-first design
    - Support 320px to 1920px viewports
    - Use Tailwind responsive utilities
    - _Requirements: 8.1, 8.7_
  
  - [~] 15.2 Implement hamburger menu for mobile (≤768px)
    - Create hamburger menu button
    - Implement slide-out navigation drawer
    - Close menu on navigation
    - _Requirements: 8.2_
  
  - [~] 15.3 Implement mobile form layout (single column)
    - Stack form fields vertically on mobile
    - Ensure tap targets are 44x44px minimum
    - Adjust layout if conflicts arise
    - _Requirements: 8.5, 8.6_
  
  - [~] 15.4 Implement mobile chart scrolling
    - Enable horizontal scroll for charts on mobile
    - Add visual indicators for scrollable content
    - _Requirements: 8.4_
  
  - [ ]* 15.5 Write property tests for responsive design
    - **Property 48: Mobile Navigation Shows Hamburger Menu**
    - **Property 49: Mobile Chart Scrolls Horizontally**
    - **Property 50: Mobile Form Uses Single Column**
    - **Validates: Requirements 8.2, 8.4, 8.5, 8.6**

- [x] 16. Implement security features (CSRF, input validation, XSS prevention)
  - [~] 16.1 Implement CSRF protection on all state-changing forms
    - Add CSRF token to all forms
    - Validate CSRF token on submission
    - Use Laravel's built-in CSRF middleware
    - _Requirements: 10.2_
  
  - [~] 16.2 Implement server-side input validation and sanitization
    - Validate all inputs on server side
    - Sanitize inputs to prevent SQL injection
    - Escape outputs to prevent XSS
    - _Requirements: 10.3_
  
  - [~] 16.3 Implement role-based data access control
    - Ensure nurses can only access their assigned unit's data
    - Ensure admins can access all data
    - Implement authorization checks in controllers
    - _Requirements: 10.5_
  
  - [~] 16.4 Ensure HTTPS and secure headers
    - Configure HTTPS in production
    - Add security headers (HSTS, CSP, X-Frame-Options)
    - _Requirements: 10.1_
  
  - [ ]* 16.5 Write property tests for security
    - **Property 54: CSRF Token Present on State-Changing Forms**
    - **Property 55: Malicious Input Sanitized**
    - **Property 56: Role-Based Data Access Control**
    - **Property 57: Session Cleanup on Logout**
    - **Validates: Requirements 10.1, 10.2, 10.3, 10.5, 10.6**

- [x] 17. Checkpoint - Ensure all reporting and security tests pass
  - Ensure all tests pass for reporting, charts, responsive design, and security
  - Ask the user if questions arise.

- [x] 18. Create main layout template and navigation
  - [~] 18.1 Create base layout Blade template
    - Include header with logo and user info
    - Include navigation sidebar/hamburger
    - Include main content area
    - Include footer
    - _Requirements: 8.1, 8.2_
  
  - [~] 18.2 Implement navigation based on user role
    - Show appropriate menu items for nurses and admins
    - Prevent access to unauthorized pages
    - _Requirements: 9.4_
  
  - [~] 18.3 Implement logout button in header
    - Add logout link in user menu
    - Implement logout action
    - _Requirements: 1.6_

- [x] 19. Implement notification system (success, error, warning, info)
  - [~] 19.1 Create notification component
    - Build toast notification UI with Tailwind
    - Support success, error, warning, info types
    - Auto-dismiss after 3 seconds
    - _Requirements: 2.4, 2.5, 6.2, 6.4, 6.6, 7.2, 7.6_
  
  - [~] 19.2 Implement notification display logic
    - Display notifications from controller responses
    - Handle multiple notifications
    - _Requirements: 2.4, 2.5_

- [x] 20. Create integration tests for complete workflows
  - [~] 20.1 Write integration test for authentication flow
    - Test login → dashboard → logout → login page
    - _Requirements: 1.1, 1.6_
  
  - [~] 20.2 Write integration test for patient data entry flow
    - Test login as nurse → form input → submit → success → data in database
    - _Requirements: 2.1, 2.2, 2.4_
  
  - [~] 20.3 Write integration test for reporting flow
    - Test login as admin → reports → apply filters → chart updates
    - _Requirements: 4.1, 5.2_
  
  - [~] 20.4 Write integration test for user management flow
    - Test login as admin → user management → create/edit/deactivate user
    - _Requirements: 7.1, 7.2, 7.6, 7.7_

- [x] 21. Final checkpoint - Ensure all tests pass and system is complete
  - Ensure all unit tests, property tests, and integration tests pass
  - Verify all requirements are implemented
  - Ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional test-related sub-tasks that can be skipped for faster MVP delivery
- Each task references specific requirements for traceability
- Property-based tests validate universal correctness properties from the design document
- Unit tests validate specific examples and edge cases
- Integration tests validate complete workflows
- Checkpoints at tasks 10, 17, and 21 ensure incremental validation
- All 57 correctness properties from the design are covered by property test sub-tasks
- The implementation follows a layered approach: foundation → authentication → core features → reporting → security
- Database migrations should be run before starting authentication implementation
- Shift detection uses Asia/Jakarta timezone (WIB/UTC+7)
- All passwords must be hashed with bcrypt before storage
- All user inputs must be validated on both client and server side
- CSRF protection must be implemented on all state-changing forms
- Role-based access control must be enforced at the controller level


## Task Dependency Graph

```json
{
  "waves": [
    {
      "id": 0,
      "tasks": ["1.1", "1.2", "1.3"]
    },
    {
      "id": 1,
      "tasks": ["1.4", "2.1", "2.2", "2.3", "2.4"]
    },
    {
      "id": 2,
      "tasks": ["2.5", "3.1", "3.2", "3.3"]
    },
    {
      "id": 3,
      "tasks": ["3.4", "4.1", "4.2", "4.3", "4.4"]
    },
    {
      "id": 4,
      "tasks": ["4.5", "5.1", "5.2", "5.3", "5.4"]
    },
    {
      "id": 5,
      "tasks": ["5.5", "6.1", "6.2"]
    },
    {
      "id": 6,
      "tasks": ["6.3", "7.1", "7.2", "7.3"]
    },
    {
      "id": 7,
      "tasks": ["7.4", "8.1", "8.2", "8.3", "8.4"]
    },
    {
      "id": 8,
      "tasks": ["8.5", "9.1", "9.2", "9.3", "9.4"]
    },
    {
      "id": 9,
      "tasks": ["9.5", "9.6"]
    },
    {
      "id": 10,
      "tasks": ["9.7"]
    },
    {
      "id": 11,
      "tasks": ["11.1", "11.2", "11.3"]
    },
    {
      "id": 12,
      "tasks": ["11.4", "12.1", "12.2", "12.3"]
    },
    {
      "id": 13,
      "tasks": ["13.1", "13.2", "13.3", "13.4"]
    },
    {
      "id": 14,
      "tasks": ["13.5", "14.1", "14.2"]
    },
    {
      "id": 15,
      "tasks": ["14.3", "15.1", "15.2", "15.3", "15.4"]
    },
    {
      "id": 16,
      "tasks": ["15.5", "16.1", "16.2", "16.3", "16.4"]
    },
    {
      "id": 17,
      "tasks": ["16.5"]
    },
    {
      "id": 18,
      "tasks": ["18.1", "18.2", "18.3"]
    },
    {
      "id": 19,
      "tasks": ["19.1", "19.2"]
    },
    {
      "id": 20,
      "tasks": ["20.1", "20.2", "20.3", "20.4"]
    }
  ]
}
```

## Implementation Notes

### Wave Execution Strategy

- **Waves 0-2**: Database and authentication foundation (must complete first)
- **Waves 3-6**: Patient data input and core features
- **Waves 7-10**: Unit and user management
- **Waves 11-17**: Reporting system and security
- **Waves 18-20**: UI polish, notifications, and integration tests

### Key Dependencies

1. **Database migrations (1.1)** must complete before any model usage
2. **Authentication (2.1-2.5)** must complete before accessing protected routes
3. **Shift detection (3.1-3.3)** must complete before patient data form
4. **Patient data storage (5.1-5.4)** must complete before reporting
5. **Report data retrieval (11.1-11.3)** must complete before chart rendering
6. **Security features (16.1-16.4)** should be implemented throughout but tested together

### Testing Strategy

- **Property tests** validate universal correctness properties (marked with *)
- **Unit tests** validate individual components
- **Integration tests** validate complete workflows
- **Checkpoints** (tasks 10, 17, 21) ensure incremental validation

### Optional Tasks

The following test-related sub-tasks are optional and can be skipped for MVP:
- 1.4, 2.5, 3.4, 4.5, 5.5, 6.3, 7.4, 8.5, 9.7, 11.4, 13.5, 14.3, 15.5, 16.5

### Estimated Timeline

- **Phase 1 (Waves 0-2)**: 2-3 days - Database and authentication
- **Phase 2 (Waves 3-6)**: 3-4 days - Patient data input
- **Phase 3 (Waves 7-10)**: 2-3 days - Management features
- **Phase 4 (Waves 11-17)**: 4-5 days - Reporting and security
- **Phase 5 (Waves 18-20)**: 2-3 days - UI polish and testing

**Total estimated time: 13-18 days for complete implementation**
