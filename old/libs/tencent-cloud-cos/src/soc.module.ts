import { Module } from '@nestjs/common';
import { TencentCloudStsModule } from '@socfony/tencent-cloud-sts';
import { TencentCloudCosService } from './cos.service';

@Module({
  imports: [TencentCloudStsModule],
  providers: [TencentCloudCosService],
  exports: [TencentCloudCosService],
})
export class TencentCloudCosModule {}
