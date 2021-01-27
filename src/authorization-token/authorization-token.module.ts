import { Module } from '@nestjs/common';
import { PrismaModule } from 'src/prisma';
import { SecurityModule } from 'src/security/security.module';
import { UserModule } from 'src/user';
import { AuthorizationTokenEntityResolver } from './authorization-token.resolver';
import { AuthorizationTokenService } from './authorization-token.service';

@Module({
  imports: [PrismaModule, UserModule, SecurityModule],
  providers: [AuthorizationTokenService, AuthorizationTokenEntityResolver],
  exports: [AuthorizationTokenService],
})
export class AuthorizationTokenModule {}
