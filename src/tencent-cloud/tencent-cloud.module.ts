import { Module } from '@nestjs/common';
import { TencentCloudCosService } from './cos';
import { TencentCloudCosResolver } from './cos/cos.resolver';
import { TencentCloudSmsService } from './sms';
import { TencentCloudStsService } from './sts';

/**
 * Tencent Cloud module.
 */
@Module({
  providers: [
    TencentCloudStsService,
    TencentCloudCosService,
    TencentCloudSmsService,

    // Resolvers
    TencentCloudCosResolver,
  ],
  exports: [
    TencentCloudStsService,
    TencentCloudCosService,
    TencentCloudSmsService,
  ],
})
export class TencentCloudModule {}
