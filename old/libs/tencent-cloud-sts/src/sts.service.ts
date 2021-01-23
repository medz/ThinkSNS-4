import { Injectable } from '@nestjs/common';
import { TencentCloudCommonService } from '@socfony/tencent-cloud-common';
import { Client } from 'tencentcloud-sdk-nodejs/tencentcloud/services/sts/v20180813/sts_client';

@Injectable()
export class TencentCloudStsService {
  constructor(private readonly common: TencentCloudCommonService) {}

  /**
   * Create STS client for region.
   * @param region server region.
   */
  createClient(region: string = 'ap-guangzhou'): Client {
    return new Client({
      credential: this.common.credential,
      region,
      profile: {
        httpProfile: {
          endpoint: 'sts.tencentcloudapi.com',
        },
      },
    });
  }
}
