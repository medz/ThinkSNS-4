import { Module } from '@nestjs/common';
import { TencentCloudCosService } from './cos';
import { TencentCloudSmsService } from './sms';
import { TencentCloudStsService } from './sts';

@Module({
  providers: [
    TencentCloudStsService,
    TencentCloudCosService,
    TencentCloudSmsService,
  ],
  exports: [
    TencentCloudStsService,
    TencentCloudCosService,
    TencentCloudSmsService,
  ],
})
export class TencentCloudModule {}
