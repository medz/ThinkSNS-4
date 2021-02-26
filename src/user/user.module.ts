import { Module } from '@nestjs/common';
import { PrismaModule } from 'src/prisma';
import { SecurityModule } from 'src/security/security.module';
import { UserProfileModule } from './profile/profile.module';
import { UserResolver } from './user.resolver';
import { UserService } from './user.service';

/**
 * User module.
 */
@Module({
  imports: [PrismaModule, UserProfileModule, SecurityModule],
  providers: [UserService, UserResolver],
  exports: [UserService],
})
export class UserModule {}
