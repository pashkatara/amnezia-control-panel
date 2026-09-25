-- Add traffic limit field to vpn_clients table
-- This migration adds traffic limit functionality to clients

ALTER TABLE vpn_clients 
ADD COLUMN traffic_limit BIGINT UNSIGNED NULL COMMENT 'Traffic limit in bytes (NULL = unlimited)' AFTER expires_at,
ADD INDEX idx_traffic_limit (traffic_limit);
