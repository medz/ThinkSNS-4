import { Module } from '@nestjs/common';
import { PrismaModule } from './prisma';
import { SecurityModule } from './security/security.module';
import { AuthorizationTokenModule } from './authorization-token/authorization-token.module';
import { UserModule } from './user/user.module';
import { APP_GUARD } from '@nestjs/core';
import { AuthorizationGuard } from './authorization.guard';
import { TencentCloudModule } from './tencent-cloud';
import { MomentModule } from './moment/moment.module';
import { GraphQLModule } from './graphql';

/**
 * Root app module.
 */
@Module({
  imports: [
    PrismaModule,
    GraphQLModule,
    AuthorizationTokenModule,
    UserModule,
    SecurityModule,
    TencentCloudModule,
    MomentModule,
  ],
  providers: [
    {
      provide: APP_GUARD,
      useClass: AuthorizationGuard,
    },
  ],
})
export class AppModule {}
