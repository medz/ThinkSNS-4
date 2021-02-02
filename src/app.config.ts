import { Logger } from '@nestjs/common';
import { config as dontEnvConfig } from 'dotenv';

/**
 * Socfony app config.
 */
export interface SocfonyConfig {
  /**
   * Is production.
   */
  isProduction: boolean;

  /**
   * App runner port
   */
  port: string | number;

  /**
   * GraphQL endpoint
   */
  endpoint: string;
}

/**
 * Load Socfony ENV config file.
 * @param path ENV file path.
 */
export function loadConfig(path?: string) {
  const logger = new Logger('Socfony');
  const { error } = dontEnvConfig({ path });
  if (error) {
    logger.error(error.message);
    process.exit(1);
  }

  logger.log(`Loaded "${path || '.env'}" dot env config file.`);
}

/**
 * Get app config.
 * @returns {@like SocfonyConfig}
 */
export function getConfig(): SocfonyConfig {
  return {
    isProduction: process.env.NODE_ENV === 'production',
    port: process.env.CLIENT_SERVER_PORT || 3000,
    endpoint: process.env.CLIENT_GRAPHQL_ENDPOINT || '/',
  };
}
