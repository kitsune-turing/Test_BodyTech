-- Migration: Create revoked_tokens table
-- Version: 2.0.0
-- Description: Table for JWT token revocation (blacklist) with automatic cleanup

CREATE TABLE IF NOT EXISTS revoked_tokens (
    jti UUID PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    revoked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL
);

-- Indexes for performance
CREATE INDEX IF NOT EXISTS idx_revoked_tokens_user_id ON revoked_tokens(user_id);
CREATE INDEX IF NOT EXISTS idx_revoked_tokens_expires_at ON revoked_tokens(expires_at);
CREATE INDEX IF NOT EXISTS idx_revoked_tokens_revoked_at ON revoked_tokens(revoked_at);

-- Comments
COMMENT ON TABLE revoked_tokens IS 'Blacklist of revoked JWT tokens for audit trail';
COMMENT ON COLUMN revoked_tokens.jti IS 'JWT ID claim (unique identifier)';
COMMENT ON COLUMN revoked_tokens.expires_at IS 'Token expiration timestamp for cleanup';

-- Function to automatically delete expired tokens (cleanup job)
CREATE OR REPLACE FUNCTION cleanup_expired_tokens()
RETURNS void AS $$
BEGIN
    DELETE FROM revoked_tokens WHERE expires_at < NOW();
END;
$$ LANGUAGE plpgsql;

-- Optional: Create a scheduled job (requires pg_cron extension)
-- SELECT cron.schedule('cleanup-expired-tokens', '0 * * * *', 'SELECT cleanup_expired_tokens()');
