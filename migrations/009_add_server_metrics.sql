-- Add server metrics tables
-- This migration adds functionality to store and display server monitoring data

-- Server metrics (CPU, RAM, Disk, Network)
CREATE TABLE IF NOT EXISTS server_metrics (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  server_id INT UNSIGNED NOT NULL,
  cpu_percent DECIMAL(5,2) NULL COMMENT 'CPU usage percentage',
  ram_used_mb INT UNSIGNED NULL COMMENT 'RAM used in MB',
  ram_total_mb INT UNSIGNED NULL COMMENT 'Total RAM in MB',
  disk_used_gb DECIMAL(10,2) NULL COMMENT 'Disk used in GB',
  disk_total_gb DECIMAL(10,2) NULL COMMENT 'Total disk in GB',
  network_rx_mbps DECIMAL(10,2) NULL COMMENT 'Network receive speed in Mbps',
  network_tx_mbps DECIMAL(10,2) NULL COMMENT 'Network transmit speed in Mbps',
  collected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_server_time (server_id, collected_at),
  FOREIGN KEY (server_id) REFERENCES vpn_servers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Client traffic metrics (speed tracking)
CREATE TABLE IF NOT EXISTS client_metrics (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  client_id INT UNSIGNED NOT NULL,
  bytes_sent BIGINT UNSIGNED DEFAULT 0 COMMENT 'Bytes sent at this moment',
  bytes_received BIGINT UNSIGNED DEFAULT 0 COMMENT 'Bytes received at this moment',
  speed_up_kbps DECIMAL(10,2) NULL COMMENT 'Upload speed in Kbps',
  speed_down_kbps DECIMAL(10,2) NULL COMMENT 'Download speed in Kbps',
  collected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_client_time (client_id, collected_at),
  FOREIGN KEY (client_id) REFERENCES vpn_clients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
