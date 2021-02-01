import { Logger } from '@nestjs/common';
import { NestFactory } from '@nestjs/core';
import { getConfig, loadConfig } from './app.config';
import { AppModule } from './app.module';

// Get Socfony ENV filename.
const [_, __, dontEnvFilename] = process.argv;

// Load ENV copnfig.
loadConfig(dontEnvFilename);

/**
 * Create SOcfony bootstrap.
 */
async function bootstrap() {
  const logger = new Logger('Socfony');
  const { port } = getConfig();
  const app = await NestFactory.create(AppModule);
  await app.listen(port);

  const url = (await app.getUrl()).replace('[::1]', '127.0.0.1');
  logger.log(`Server listening on ${url}`);
}

// run app.
bootstrap();
