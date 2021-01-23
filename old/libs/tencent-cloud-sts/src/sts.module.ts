import { Module } from '@nestjs/common';
import { TencentCloudCommonModule } from '@socfony/tencent-cloud-common';
import { TencentCloudStsService } from './sts.service';

@Module({
  imports: [TencentCloudCommonModule],
  providers: [TencentCloudStsService],
  exports: [TencentCloudStsService],
})
export class TencentCloudStsModule {}
