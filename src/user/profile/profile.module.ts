import { Module } from '@nestjs/common';
import { PrismaModule } from 'src/prisma';
import { UserProfileService } from './profile.service';

@Module({
  imports: [PrismaModule],
  providers: [UserProfileService],
  exports: [UserProfileService],
})
export class UserProfileModule {}
