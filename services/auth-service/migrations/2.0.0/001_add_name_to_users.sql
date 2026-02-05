-- Migration: Add name column to users table
-- Version: 2.0.0
-- Description: Add name field for user profile

ALTER TABLE users
ADD COLUMN IF NOT EXISTS name VARCHAR(255);

-- Update comments
COMMENT ON COLUMN users.name IS 'User full name';
