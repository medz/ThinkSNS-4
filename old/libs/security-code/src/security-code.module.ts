import { Module } from '@nestjs/common';
import { PrismaModule } from '@socfony/prisma';
import { TencentCloudSmsModule } from '@socfony/tencent-cloud-sms';
import { SecurityCodeService } from './security-code.service';

/**
 * Sceurity code module.
 */
@Module({
  imports: [PrismaModule, TencentCloudSmsModule],
  providers: [SecurityCodeService],
  exports: [SecurityCodeService],
})
export class SecurityCodeModule {}
