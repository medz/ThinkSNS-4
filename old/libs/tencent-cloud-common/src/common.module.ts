import { Module } from '@nestjs/common';
import { TencentCloudCommonService } from './common.service';

@Module({
  providers: [TencentCloudCommonService],
  exports: [TencentCloudCommonService],
})
export class TencentCloudCommonModule {}
