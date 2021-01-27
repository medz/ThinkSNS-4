import { Logger } from '@nestjs/common';
import { NestFactory } from '@nestjs/core';
import { getConfig, loadConfig } from './app.config';
import { AppModule } from './app.module';

const [_, __, dontEnvFilename] = process.argv;
loadConfig(dontEnvFilename);

async function bootstrap() {
  const logger = new Logger('Socfony');
  const { port } = getConfig();
  const app = await NestFactory.create(AppModule);
  await app.listen(port);

  const url = (await app.getUrl()).replace('[::1]', '127.0.0.1');
  logger.log(`Server listening on ${url}`);
}

bootstrap();
