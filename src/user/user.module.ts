import { Module } from '@nestjs/common';
import { PrismaModule } from 'src/prisma';
import { UserProfileModule } from './profile/profile.module';
import { UserInterfaceResolver } from './user-interface.resolver';
import { UserService } from './user.service';

@Module({
  imports: [PrismaModule, UserProfileModule],
  providers: [UserService, UserInterfaceResolver],
  exports: [UserService],
})
export class UserModule {}
