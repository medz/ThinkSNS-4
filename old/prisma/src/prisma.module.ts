import {
  ClassProvider,
  Module,
  OnModuleDestroy,
  OnModuleInit,
} from '@nestjs/common';
import { PrismaClient } from '@prisma/client';

const client: ClassProvider<PrismaClient> = {
  provide: PrismaClient,
  useClass: class
    extends PrismaClient
    implements OnModuleInit, OnModuleDestroy {
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

@Module({
  providers: [client],
  exports: [client],
})
export class PrismaModule {}
