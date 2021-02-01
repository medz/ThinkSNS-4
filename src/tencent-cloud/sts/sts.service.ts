import { Injectable } from '@nestjs/common';
import { Client } from 'tencentcloud-sdk-nodejs/tencentcloud/services/sts/v20180813/sts_client';
import { getCredential } from '../common';

/**
 * Tencent Cloud STS service.
 */
@Injectable()
export class TencentCloudStsService {
  /**
   * Create STS client for region.
   * @param region server region.
   */
  createClient(region: string = 'ap-guangzhou'): Client {
    return new Client({
      credential: getCredential(),
      region,
      profile: {
        httpProfile: {
          endpoint: 'sts.tencentcloudapi.com',
        },
      },
    });
  }
}
