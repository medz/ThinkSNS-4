import { Module } from '@nestjs/common';
import { PrismaModule } from 'src/prisma';
import { SecurityModule } from 'src/security/security.module';
import { UserProfileModule } from './profile/profile.module';
import { UserInterfaceResolver } from './user-interface.resolver';
import { UserService } from './user.service';
import { ViewerResolver } from './viewer.resolver';

/**
 * User module.
 */
@Module({
  imports: [PrismaModule, UserProfileModule, SecurityModule],
  providers: [UserService, UserInterfaceResolver, ViewerResolver],
  exports: [UserService],
})
export class UserModule {}
