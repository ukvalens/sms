-- Add photo column to users table for admin profile images
ALTER TABLE users ADD COLUMN photo VARCHAR(255) AFTER status;
