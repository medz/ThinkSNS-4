import {
  ClassProvider,
  Global,
  Logger,
  Module,
  OnModuleDestroy,
  OnModuleInit,
} from '@nestjs/common';
import { PrismaClient } from '@prisma/client';
import { PrismaLoggerMiddleware } from './middleware';

/**
 * Prisma class provider
 */
const client: ClassProvider<PrismaClient> = {
  provide: PrismaClient,
  useClass: class
    extends PrismaClient
    implements OnModuleInit, OnModuleDestroy {
    private readonly logger = new Logger('Prisma');

    /**
     * Prisma client constructor
     */
    constructor() {
      super({
        datasources: {
          database: { url: process.env.DATABASE_URL },
        },
      });
    }

    /**
     * NestJS module init hook callback.
     */
    async onModuleInit() {
      // Prisma connect database.
      await this.$connect();

      // Set Prisma client global logger middleware.
      this.$use(PrismaLoggerMiddleware(this.logger));
    }

    /**
     * NestJS module destory hook callback.
     */
    async onModuleDestroy() {
      // Prisma disconnect database.
      await this.$disconnect();
    }
  },
};

/**
 * Prisma module.
 */
@Global()
@Module({
  providers: [client],
  exports: [client],
})
export class PrismaModule {}
