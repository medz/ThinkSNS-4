import { Module } from '@nestjs/common';
import { AuthModule as _ } from '@socfony/auth';
import { KernelModule } from '@socfony/kernel';
import { PrismaModule } from '@socfony/prisma';
import { TencentCloudCosModule } from '@socfony/tencent-cloud-cos';
import { AuthResolver } from './auth.resolver';
import { CosAuthorizationResolver } from './cos.resolver';

@Module({
  imports: [_, KernelModule, PrismaModule, TencentCloudCosModule],
  providers: [AuthResolver, CosAuthorizationResolver],
})
export class AuthModule {}
