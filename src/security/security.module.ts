import { Module } from '@nestjs/common';
import { PrismaModule } from 'src/prisma';
import { TencentCloudModule } from 'src/tencent-cloud';
import { SecuritySmsResolver } from './security-sms.resolver';
import { SecuritySmsService } from './security-sms.service';

/**
 * Security module.
 */
@Module({
  imports: [TencentCloudModule, PrismaModule],
  providers: [SecuritySmsService, SecuritySmsResolver],
  exports: [SecuritySmsService],
})
export class SecurityModule {}
