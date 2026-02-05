-- Migration: Create tasks table
-- Version: 1.0.0
-- Description: Tasks table with status enum and user ownership

-- Create enum type for task status
DO $$ BEGIN
    CREATE TYPE task_status AS ENUM ('pending', 'in_progress', 'done');
EXCEPTION
    WHEN duplicate_object THEN null;
END $$;

CREATE TABLE IF NOT EXISTS tasks (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    status task_status DEFAULT 'pending' NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Indexes for performance
CREATE INDEX IF NOT EXISTS idx_tasks_user_id ON tasks(user_id);
CREATE INDEX IF NOT EXISTS idx_tasks_status ON tasks(status);
CREATE INDEX IF NOT EXISTS idx_tasks_user_status ON tasks(user_id, status);
CREATE INDEX IF NOT EXISTS idx_tasks_created_at ON tasks(created_at);

-- Trigger to auto-update updated_at
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER update_tasks_updated_at
    BEFORE UPDATE ON tasks
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

-- Comments
COMMENT ON TABLE tasks IS 'User tasks with ownership and status tracking';
COMMENT ON COLUMN tasks.user_id IS 'Logical reference to user (no FK for microservices independence)';
COMMENT ON COLUMN tasks.title IS 'Task title (required, 1-255 characters)';
COMMENT ON COLUMN tasks.description IS 'Optional task description';
COMMENT ON COLUMN tasks.status IS 'Task status: pending, in_progress, or done';
