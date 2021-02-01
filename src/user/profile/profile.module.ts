import { Module } from '@nestjs/common';
import { PrismaModule } from 'src/prisma';
import { UserProfileResolver } from './profile.resolver';
import { UserProfileService } from './profile.service';

/**
 * User profile module.
 */
@Module({
  imports: [PrismaModule],
  providers: [UserProfileService, UserProfileResolver],
  exports: [UserProfileService],
})
export class UserProfileModule {}
