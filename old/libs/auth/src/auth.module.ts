import { Module } from '@nestjs/common';
import { KernelModule } from '@socfony/kernel';
import { PrismaModule } from '@socfony/prisma';
import { SecurityCodeModule } from '@socfony/security-code';
import { AuthGuard } from './auth.guard';
import { AuthService } from './auth.service';

@Module({
  imports: [KernelModule, PrismaModule, SecurityCodeModule],
  providers: [AuthGuard, AuthService],
  exports: [AuthService],
})
export class AuthModule {}
