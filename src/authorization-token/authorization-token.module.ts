import { Global, Module } from '@nestjs/common';
import { PrismaModule } from 'src/prisma';
import { UserModule } from 'src/user/user.module';
import { AuthorizationTokenEntityResolver } from './authorization-token.resolver';
import { AuthorizationTokenService } from './authorization-token.service';

/**
 * Authorization token module.
 */
@Global()
@Module({
  imports: [PrismaModule, UserModule],
  providers: [AuthorizationTokenService, AuthorizationTokenEntityResolver],
  exports: [AuthorizationTokenService],
})
export class AuthorizationTokenModule {}
