import { Module } from '@nestjs/common';
import { AuthModule } from '@socfony/auth';
import { KernelModule } from '@socfony/kernel';
import { PrismaModule } from '@socfony/prisma';
import { UserProfileResolver } from './profile.resolver';
import { UserProfileService } from './profile.service';

@Module({
  imports: [PrismaModule, KernelModule, AuthModule],
  providers: [UserProfileService, UserProfileResolver],
  exports: [UserProfileService],
})
export class UserProfileModule {}
