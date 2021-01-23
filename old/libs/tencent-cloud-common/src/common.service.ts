import { Injectable } from '@nestjs/common';
import { Credential } from 'tencentcloud-sdk-nodejs/tencentcloud/common/interface';

@Injectable()
export class TencentCloudCommonService {
  /**
   * Get Tencent Cloud common credential.
   */
  get credential(): Credential {
    return {
      secretId: process.env.TENCENT_CLOUD_SECRET_ID,
      secretKey: process.env.TENCENT_CLOUD_SECRET_KEY,
    };
  }
}
