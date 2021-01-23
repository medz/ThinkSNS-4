import { Module } from '@nestjs/common';
import { KernelModule } from '@socfony/kernel';
import { PrismaModule } from '@socfony/prisma';
import { UserModule as _ } from '@socfony/user';
import { UserProfileModule } from './profile';

@Module({
  imports: [_, KernelModule, PrismaModule, UserProfileModule],
})
export class UserModule {}
