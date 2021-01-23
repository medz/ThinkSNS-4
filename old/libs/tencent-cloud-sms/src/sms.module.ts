import { Module } from '@nestjs/common';
import { TencentCloudCommonModule } from '@socfony/tencent-cloud-common';
import { TencentCloudSmsService } from './sms.service';

@Module({
  imports: [TencentCloudCommonModule],
  providers: [TencentCloudSmsService],
  exports: [TencentCloudSmsService],
})
export class TencentCloudSmsModule {}
