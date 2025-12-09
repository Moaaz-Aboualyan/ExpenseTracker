-- ========================================
-- DUMMY DATA FOR EXPENSE TRACKER
-- ========================================
-- This file contains comprehensive sample data from January 2024 to December 2025
-- covering various usage patterns including:
-- - Multiple income sources with recurring income
-- - Various expense categories
-- - Different transaction frequencies
-- - Budget tracking
-- - Seasonal spending patterns

-- NOTE: Make sure to insert a test user first or update the user_id (1) to match your actual user ID

-- ========================================
-- 1. CREATE TEST USER
-- ========================================
-- Option A: Create a new test user (password is 'password123')
INSERT INTO users (name, email, password_hash) VALUES 
('Test User', 'testuser@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Get the user_id we just created
SET @user_id = LAST_INSERT_ID();

-- Option B: If you want to use an existing user instead, comment out lines above and uncomment below:
-- SET @user_id = 1; -- Replace 1 with your actual user_id from the users table

-- ========================================
-- 2. CREATE INCOME CATEGORIES WITH RECURRING SETUP
-- ========================================
INSERT INTO categories (user_id, name, type, recurring_frequency, recurring_amount, recurring_date, last_recurring_date) VALUES
(@user_id, 'Salary', 'income', 'monthly', 5000.00, 1, '2024-12-01'),
(@user_id, 'Freelance Work', 'income', NULL, NULL, NULL, NULL),
(@user_id, 'Bonus', 'income', NULL, NULL, NULL, NULL),
(@user_id, 'Investment Returns', 'income', 'monthly', 150.00, 15, '2024-12-15'),
(@user_id, 'Side Gig', 'income', NULL, NULL, NULL, NULL);

-- ========================================
-- 3. CREATE EXPENSE CATEGORIES WITH BUDGETS
-- ========================================
INSERT INTO categories (user_id, name, type, monthly_budget) VALUES
(@user_id, 'Rent', 'expense', 1500.00),
(@user_id, 'Groceries', 'expense', 600.00),
(@user_id, 'Dining Out', 'expense', 300.00),
(@user_id, 'Transportation', 'expense', 250.00),
(@user_id, 'Utilities', 'expense', 200.00),
(@user_id, 'Entertainment', 'expense', 150.00),
(@user_id, 'Shopping', 'expense', 400.00),
(@user_id, 'Gym Membership', 'expense', 50.00),
(@user_id, 'Subscriptions', 'expense', 100.00),
(@user_id, 'Insurance', 'expense', 300.00),
(@user_id, 'Medical', 'expense', 100.00),
(@user_id, 'Gas/Fuel', 'expense', 150.00),
(@user_id, 'Phone Bill', 'expense', 80.00),
(@user_id, 'Internet', 'expense', 60.00),
(@user_id, 'Travel', 'expense', 200.00),
(@user_id, 'Books', 'expense', 50.00);

-- ========================================
-- 4. INSERT INCOME TRANSACTIONS
-- ========================================
-- January 2024
INSERT INTO transactions (user_id, category_id, type, amount, date, note) VALUES
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Salary'), 'income', 5000.00, '2024-01-01', 'Monthly salary'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Freelance Work'), 'income', 1200.00, '2024-01-08', 'Web design project'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Investment Returns'), 'income', 150.00, '2024-01-15', 'Monthly dividend payment'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Side Gig'), 'income', 350.00, '2024-01-20', 'Freelance tutoring');

-- February 2024
INSERT INTO transactions (user_id, category_id, type, amount, date, note) VALUES
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Salary'), 'income', 5000.00, '2024-02-01', 'Monthly salary'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Investment Returns'), 'income', 150.00, '2024-02-15', 'Monthly dividend payment'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Bonus'), 'income', 800.00, '2024-02-28', 'Performance bonus');

-- March 2024
INSERT INTO transactions (user_id, category_id, type, amount, date, note) VALUES
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Salary'), 'income', 5000.00, '2024-03-01', 'Monthly salary'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Freelance Work'), 'income', 1500.00, '2024-03-10', 'Website redesign'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Investment Returns'), 'income', 150.00, '2024-03-15', 'Monthly dividend payment');

-- Continuing monthly salary and investment returns through December 2025
-- (Adding key months with variations)

-- April 2024
INSERT INTO transactions (user_id, category_id, type, amount, date, note) VALUES
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Salary'), 'income', 5000.00, '2024-04-01', 'Monthly salary'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Investment Returns'), 'income', 150.00, '2024-04-15', 'Monthly dividend payment'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Side Gig'), 'income', 420.00, '2024-04-25', 'Online tutoring sessions');

-- May-December 2024 (Monthly salaries and investments)
INSERT INTO transactions (user_id, category_id, type, amount, date, note) VALUES
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Salary'), 'income', 5000.00, '2024-05-01', 'Monthly salary'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Investment Returns'), 'income', 150.00, '2024-05-15', 'Monthly dividend payment'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Salary'), 'income', 5000.00, '2024-06-01', 'Monthly salary'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Investment Returns'), 'income', 150.00, '2024-06-15', 'Monthly dividend payment'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Bonus'), 'income', 1500.00, '2024-06-30', 'Mid-year bonus'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Salary'), 'income', 5000.00, '2024-07-01', 'Monthly salary'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Investment Returns'), 'income', 150.00, '2024-07-15', 'Monthly dividend payment'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Salary'), 'income', 5000.00, '2024-08-01', 'Monthly salary'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Investment Returns'), 'income', 150.00, '2024-08-15', 'Monthly dividend payment'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Freelance Work'), 'income', 900.00, '2024-08-22', 'Logo design'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Salary'), 'income', 5000.00, '2024-09-01', 'Monthly salary'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Investment Returns'), 'income', 150.00, '2024-09-15', 'Monthly dividend payment'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Salary'), 'income', 5000.00, '2024-10-01', 'Monthly salary'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Investment Returns'), 'income', 150.00, '2024-10-15', 'Monthly dividend payment'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Salary'), 'income', 5000.00, '2024-11-01', 'Monthly salary'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Investment Returns'), 'income', 150.00, '2024-11-15', 'Monthly dividend payment'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Bonus'), 'income', 2000.00, '2024-11-30', 'Year-end bonus (early)'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Salary'), 'income', 5000.00, '2024-12-01', 'Monthly salary'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Investment Returns'), 'income', 150.00, '2024-12-15', 'Monthly dividend payment');

-- 2025 Income
INSERT INTO transactions (user_id, category_id, type, amount, date, note) VALUES
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Salary'), 'income', 5000.00, '2025-01-01', 'Monthly salary'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Investment Returns'), 'income', 150.00, '2025-01-15', 'Monthly dividend payment'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Salary'), 'income', 5000.00, '2025-02-01', 'Monthly salary'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Investment Returns'), 'income', 150.00, '2025-02-15', 'Monthly dividend payment'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Salary'), 'income', 5000.00, '2025-03-01', 'Monthly salary'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Investment Returns'), 'income', 150.00, '2025-03-15', 'Monthly dividend payment'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Salary'), 'income', 5000.00, '2025-04-01', 'Monthly salary'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Investment Returns'), 'income', 150.00, '2025-04-15', 'Monthly dividend payment'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Salary'), 'income', 5000.00, '2025-05-01', 'Monthly salary'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Investment Returns'), 'income', 150.00, '2025-05-15', 'Monthly dividend payment'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Salary'), 'income', 5000.00, '2025-06-01', 'Monthly salary'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Investment Returns'), 'income', 150.00, '2025-06-15', 'Monthly dividend payment'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Bonus'), 'income', 1500.00, '2025-06-30', 'Mid-year bonus'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Salary'), 'income', 5000.00, '2025-07-01', 'Monthly salary'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Investment Returns'), 'income', 150.00, '2025-07-15', 'Monthly dividend payment'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Salary'), 'income', 5000.00, '2025-08-01', 'Monthly salary'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Investment Returns'), 'income', 150.00, '2025-08-15', 'Monthly dividend payment'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Salary'), 'income', 5000.00, '2025-09-01', 'Monthly salary'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Investment Returns'), 'income', 150.00, '2025-09-15', 'Monthly dividend payment'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Salary'), 'income', 5000.00, '2025-10-01', 'Monthly salary'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Investment Returns'), 'income', 150.00, '2025-10-15', 'Monthly dividend payment'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Salary'), 'income', 5000.00, '2025-11-01', 'Monthly salary'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Investment Returns'), 'income', 150.00, '2025-11-15', 'Monthly dividend payment'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Bonus'), 'income', 2500.00, '2025-11-30', 'Year-end bonus');

-- ========================================
-- 5. INSERT EXPENSE TRANSACTIONS - January 2024
-- ========================================
INSERT INTO transactions (user_id, category_id, type, amount, date, note) VALUES
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Groceries'), 'expense', 125.50, '2024-01-02', 'Weekly groceries'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Groceries'), 'expense', 98.75, '2024-01-09', 'Weekly groceries'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Dining Out'), 'expense', 45.20, '2024-01-05', 'Lunch with colleague'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Dining Out'), 'expense', 78.90, '2024-01-12', 'Dinner at restaurant'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Gas/Fuel'), 'expense', 52.00, '2024-01-08', 'Fuel'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Rent'), 'expense', 1500.00, '2024-01-01', 'Monthly rent'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Utilities'), 'expense', 85.00, '2024-01-15', 'Electric bill'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Phone Bill'), 'expense', 80.00, '2024-01-20', 'Monthly phone bill'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Internet'), 'expense', 60.00, '2024-01-20', 'Monthly internet'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Shopping'), 'expense', 156.00, '2024-01-22', 'Clothes shopping'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Entertainment'), 'expense', 25.00, '2024-01-14', 'Movie tickets'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Books'), 'expense', 32.50, '2024-01-25', 'Purchase 2 books'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Gym Membership'), 'expense', 50.00, '2024-01-01', 'Monthly gym fee'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Subscriptions'), 'expense', 14.99, '2024-01-01', 'Streaming service'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Subscriptions'), 'expense', 12.99, '2024-01-05', 'Cloud storage'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Medical'), 'expense', 45.00, '2024-01-17', 'Pharmacy');

-- February 2024
INSERT INTO transactions (user_id, category_id, type, amount, date, note) VALUES
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Rent'), 'expense', 1500.00, '2024-02-01', 'Monthly rent'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Groceries'), 'expense', 110.20, '2024-02-03', 'Weekly groceries'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Groceries'), 'expense', 134.60, '2024-02-10', 'Weekly groceries'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Dining Out'), 'expense', 62.30, '2024-02-08', 'Valentine dinner'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Dining Out'), 'expense', 35.50, '2024-02-15', 'Quick lunch'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Shopping'), 'expense', 89.99, '2024-02-18', 'Valentine gift'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Gas/Fuel'), 'expense', 48.50, '2024-02-10', 'Fuel'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Entertainment'), 'expense', 50.00, '2024-02-14', 'Concert tickets'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Insurance'), 'expense', 300.00, '2024-02-01', 'Car insurance');

-- March 2024 (Spring break travel)
INSERT INTO transactions (user_id, category_id, type, amount, date, note) VALUES
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Rent'), 'expense', 1500.00, '2024-03-01', 'Monthly rent'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Groceries'), 'expense', 105.00, '2024-03-05', 'Weekly groceries'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Travel'), 'expense', 280.00, '2024-03-15', 'Hotel for weekend trip'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Travel'), 'expense', 120.00, '2024-03-16', 'Gas for road trip'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Dining Out'), 'expense', 95.40, '2024-03-16', 'Restaurant during trip'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Entertainment'), 'expense', 40.00, '2024-03-17', 'Activity during trip'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Shopping'), 'expense', 125.50, '2024-03-20', 'Spring clothing'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Utilities'), 'expense', 92.00, '2024-03-15', 'Electric bill');

-- April 2024
INSERT INTO transactions (user_id, category_id, type, amount, date, note) VALUES
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Rent'), 'expense', 1500.00, '2024-04-01', 'Monthly rent'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Groceries'), 'expense', 128.75, '2024-04-06', 'Weekly groceries'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Groceries'), 'expense', 115.30, '2024-04-13', 'Weekly groceries'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Dining Out'), 'expense', 58.20, '2024-04-08', 'Lunch'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Gas/Fuel'), 'expense', 55.00, '2024-04-12', 'Fuel'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Medical'), 'expense', 80.00, '2024-04-10', 'Doctor visit copay');

-- May - December 2024 (Regular expenses with seasonal variations)
INSERT INTO transactions (user_id, category_id, type, amount, date, note) VALUES
-- May 2024
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Rent'), 'expense', 1500.00, '2024-05-01', 'Monthly rent'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Groceries'), 'expense', 122.00, '2024-05-04', 'Weekly groceries'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Groceries'), 'expense', 135.50, '2024-05-11', 'Weekly groceries'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Dining Out'), 'expense', 72.10, '2024-05-10', 'Birthday dinner'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Entertainment'), 'expense', 120.00, '2024-05-25', 'Concert tickets'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Utilities'), 'expense', 110.00, '2024-05-15', 'Electric bill (higher for AC)'),
-- June 2024 (Summer season - higher entertainment)
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Rent'), 'expense', 1500.00, '2024-06-01', 'Monthly rent'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Groceries'), 'expense', 140.00, '2024-06-03', 'Weekly groceries'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Entertainment'), 'expense', 35.00, '2024-06-08', 'Movie night'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Travel'), 'expense', 450.00, '2024-06-15', 'Summer vacation flight'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Travel'), 'expense', 200.00, '2024-06-15', 'Hotel for vacation'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Dining Out'), 'expense', 145.30, '2024-06-18', 'Nice dinner during vacation'),
-- July 2024
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Rent'), 'expense', 1500.00, '2024-07-01', 'Monthly rent'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Groceries'), 'expense', 130.00, '2024-07-08', 'Weekly groceries'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Shopping'), 'expense', 95.00, '2024-07-12', 'Summer clothes'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Entertainment'), 'expense', 60.00, '2024-07-20', 'Concert'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Utilities'), 'expense', 125.00, '2024-07-16', 'Electric bill (peak summer)'),
-- August 2024
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Rent'), 'expense', 1500.00, '2024-08-01', 'Monthly rent'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Groceries'), 'expense', 115.40, '2024-08-05', 'Weekly groceries'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Dining Out'), 'expense', 88.50, '2024-08-12', 'Restaurant'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Medical'), 'expense', 150.00, '2024-08-14', 'Annual checkup'),
-- September 2024 (Back to school season - reduced)
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Rent'), 'expense', 1500.00, '2024-09-01', 'Monthly rent'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Groceries'), 'expense', 120.00, '2024-09-02', 'Weekly groceries'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Shopping'), 'expense', 200.00, '2024-09-05', 'Fall wardrobe'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Entertainment'), 'expense', 45.00, '2024-09-21', 'Movie'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Utilities'), 'expense', 95.00, '2024-09-16', 'Electric bill'),
-- October 2024 (Halloween)
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Rent'), 'expense', 1500.00, '2024-10-01', 'Monthly rent'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Groceries'), 'expense', 125.75, '2024-10-07', 'Weekly groceries'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Shopping'), 'expense', 65.00, '2024-10-20', 'Halloween costume'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Entertainment'), 'expense', 75.00, '2024-10-26', 'Halloween party'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Dining Out'), 'expense', 55.20, '2024-10-31', 'Halloween dinner'),
-- November 2024 (Thanksgiving, holiday season prep)
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Rent'), 'expense', 1500.00, '2024-11-01', 'Monthly rent'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Groceries'), 'expense', 185.00, '2024-11-08', 'Thanksgiving groceries'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Groceries'), 'expense', 98.50, '2024-11-15', 'Weekly groceries'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Dining Out'), 'expense', 65.00, '2024-11-28', 'Thanksgiving dinner out'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Shopping'), 'expense', 250.00, '2024-11-29', 'Black Friday shopping'),
-- December 2024 (Holiday season - high spending)
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Rent'), 'expense', 1500.00, '2024-12-01', 'Monthly rent'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Groceries'), 'expense', 140.00, '2024-12-02', 'Holiday groceries'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Shopping'), 'expense', 180.00, '2024-12-10', 'Christmas gifts'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Shopping'), 'expense', 120.00, '2024-12-15', 'More Christmas gifts'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Dining Out'), 'expense', 105.00, '2024-12-20', 'Holiday party dinner'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Entertainment'), 'expense', 50.00, '2024-12-22', 'Holiday activity'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Travel'), 'expense', 180.00, '2024-12-23', 'Gas for holiday travel');

-- ========================================
-- 6. INSERT EXPENSE TRANSACTIONS - 2025
-- ========================================
-- January 2025 (Post-holiday recovery)
INSERT INTO transactions (user_id, category_id, type, amount, date, note) VALUES
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Rent'), 'expense', 1500.00, '2025-01-01', 'Monthly rent'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Groceries'), 'expense', 118.00, '2025-01-06', 'Weekly groceries'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Groceries'), 'expense', 125.50, '2025-01-13', 'Weekly groceries'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Dining Out'), 'expense', 40.00, '2025-01-10', 'Lunch'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Gym Membership'), 'expense', 50.00, '2025-01-01', 'Gym membership'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Entertainment'), 'expense', 30.00, '2025-01-18', 'Movie'),
-- February 2025
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Rent'), 'expense', 1500.00, '2025-02-01', 'Monthly rent'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Groceries'), 'expense', 110.75, '2025-02-03', 'Weekly groceries'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Dining Out'), 'expense', 85.00, '2025-02-14', 'Valentine dinner'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Shopping'), 'expense', 75.00, '2025-02-12', 'Valentine gift'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Insurance'), 'expense', 300.00, '2025-02-01', 'Car insurance'),
-- March 2025
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Rent'), 'expense', 1500.00, '2025-03-01', 'Monthly rent'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Groceries'), 'expense', 130.00, '2025-03-05', 'Weekly groceries'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Gas/Fuel'), 'expense', 52.00, '2025-03-10', 'Fuel'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Medical'), 'expense', 60.00, '2025-03-12', 'Pharmacy'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Dining Out'), 'expense', 48.50, '2025-03-15', 'Lunch'),
-- April 2025
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Rent'), 'expense', 1500.00, '2025-04-01', 'Monthly rent'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Groceries'), 'expense', 125.00, '2025-04-07', 'Weekly groceries'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Shopping'), 'expense', 110.00, '2025-04-18', 'Spring wardrobe'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Utilities'), 'expense', 88.00, '2025-04-15', 'Electric bill'),
-- May 2025
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Rent'), 'expense', 1500.00, '2025-05-01', 'Monthly rent'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Groceries'), 'expense', 135.50, '2025-05-05', 'Weekly groceries'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Dining Out'), 'expense', 75.00, '2025-05-16', 'Birthday celebration'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Entertainment'), 'expense', 65.00, '2025-05-24', 'Concert'),
-- June 2025 (Summer season)
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Rent'), 'expense', 1500.00, '2025-06-01', 'Monthly rent'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Groceries'), 'expense', 145.00, '2025-06-09', 'Weekly groceries'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Travel'), 'expense', 500.00, '2025-06-20', 'Summer vacation flight'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Travel'), 'expense', 280.00, '2025-06-20', 'Hotel stay'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Utilities'), 'expense', 130.00, '2025-06-15', 'Electric bill (AC usage)'),
-- July 2025
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Rent'), 'expense', 1500.00, '2025-07-01', 'Monthly rent'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Groceries'), 'expense', 120.00, '2025-07-07', 'Weekly groceries'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Entertainment'), 'expense', 80.00, '2025-07-19', 'Summer entertainment'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Dining Out'), 'expense', 62.30, '2025-07-22', 'Restaurant'),
-- August 2025
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Rent'), 'expense', 1500.00, '2025-08-01', 'Monthly rent'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Groceries'), 'expense', 118.75, '2025-08-04', 'Weekly groceries'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Dining Out'), 'expense', 95.00, '2025-08-15', 'Nice dinner'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Medical'), 'expense', 200.00, '2025-08-20', 'Annual physical'),
-- September 2025
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Rent'), 'expense', 1500.00, '2025-09-01', 'Monthly rent'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Groceries'), 'expense', 125.50, '2025-09-01', 'Weekly groceries'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Shopping'), 'expense', 180.00, '2025-09-10', 'Fall wardrobe'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Entertainment'), 'expense', 40.00, '2025-09-20', 'Movie'),
-- October 2025
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Rent'), 'expense', 1500.00, '2025-10-01', 'Monthly rent'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Groceries'), 'expense', 130.00, '2025-10-06', 'Weekly groceries'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Shopping'), 'expense', 70.00, '2025-10-18', 'Halloween costume'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Entertainment'), 'expense', 80.00, '2025-10-25', 'Halloween event'),
-- November 2025
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Rent'), 'expense', 1500.00, '2025-11-01', 'Monthly rent'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Groceries'), 'expense', 175.00, '2025-11-10', 'Thanksgiving groceries'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Shopping'), 'expense', 220.00, '2025-11-28', 'Black Friday shopping'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Dining Out'), 'expense', 60.00, '2025-11-27', 'Thanksgiving dinner'),
-- December 2025 (Current - Holiday season)
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Rent'), 'expense', 1500.00, '2025-12-01', 'Monthly rent'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Groceries'), 'expense', 150.00, '2025-12-01', 'Holiday groceries'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Shopping'), 'expense', 200.00, '2025-12-05', 'Christmas gifts'),
(@user_id, (SELECT id FROM categories WHERE user_id=@user_id AND name='Shopping'), 'expense', 150.00, '2025-12-08', 'More gifts');

-- ========================================
-- 7. QUICK PRESETS FOR COMMON TRANSACTIONS
-- ========================================
INSERT INTO quick_presets (user_id, label, type, amount, category_id, note) VALUES
(@user_id, 'Coffee', 'expense', 5.50, (SELECT id FROM categories WHERE user_id=@user_id AND name='Dining Out'), 'Quick coffee purchase'),
(@user_id, 'Gas Fill-up', 'expense', 50.00, (SELECT id FROM categories WHERE user_id=@user_id AND name='Gas/Fuel'), 'Typical gas purchase'),
(@user_id, 'Grocery Run', 'expense', 120.00, (SELECT id FROM categories WHERE user_id=@user_id AND name='Groceries'), 'Weekly grocery shopping'),
(@user_id, 'Movie Night', 'expense', 35.00, (SELECT id FROM categories WHERE user_id=@user_id AND name='Entertainment'), 'Movie ticket + snacks'),
(@user_id, 'Freelance Income', 'income', 500.00, (SELECT id FROM categories WHERE user_id=@user_id AND name='Freelance Work'), 'Small freelance project');

-- ========================================
-- SUMMARY OF DUMMY DATA
-- ========================================
-- Total transactions: ~150+ covering 24 months (Jan 2024 - Dec 2025)
-- Income categories: 5 (including recurring salary and investments)
-- Expense categories: 15 with various budgets
-- Recurring transactions: Monthly salary and investments
-- Seasonal variations: Summer travel, holiday shopping, heating/cooling
-- Variety of spending patterns: Daily, weekly, monthly
-- Quick presets: 5 common transactions for rapid entry
-- ========================================
