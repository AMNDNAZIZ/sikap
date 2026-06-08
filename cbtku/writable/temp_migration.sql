ALTER TABLE users ADD COLUMN last_ip VARCHAR(45) NULL AFTER active_session_id;
ALTER TABLE users ADD COLUMN user_agent VARCHAR(255) NULL AFTER last_ip;
