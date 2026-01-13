-- Create root user account for developer access
-- This user has access to the Developer Panel with advanced settings

-- First, check if root user already exists and delete if present
DELETE FROM librarians WHERE username = 'root';

-- Create root user with password 'root123' (change this after first login!)
-- Password hash for 'root123'
INSERT INTO librarians (username, password, role) 
VALUES ('root', '$2y$10$xBx5vqXK7bZ9mKxdH4kV0.8J3nQh3F7eQVb7Ux5YnKqX8mN2dWvJG', 'root');

-- Verify the user was created
SELECT id, username, role FROM librarians WHERE username = 'root';

-- Note: The default password is 'root123'
-- You should change this immediately after first login
