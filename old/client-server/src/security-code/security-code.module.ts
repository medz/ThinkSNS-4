import { Module } from '@nestjs/common';
import { AuthModule } from '@socfony/auth';
import { KernelModule } from '@socfony/kernel';
import { SecurityCodeModule as _ } from '@socfony/security-code';
import { SecurityCodeResolver } from './security-code.resolver';

@Module({
  imports: [_, KernelModule, AuthModule],
  providers: [SecurityCodeResolver],
})
export class SecurityCodeModule {}
