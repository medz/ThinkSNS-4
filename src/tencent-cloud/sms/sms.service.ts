import { Injectable } from '@nestjs/common';
import { Client } from 'tencentcloud-sdk-nodejs/tencentcloud/services/sms/v20190711/sms_client';
import { SendSmsRequest } from 'tencentcloud-sdk-nodejs/tencentcloud/services/sms/v20190711/sms_models';
import { getCredential } from '../common';

@Injectable()
export class TencentCloudSmsService {
  get baseOptions(): Pick<
    SendSmsRequest,
    'SmsSdkAppid' | 'Sign' | 'ExtendCode' | 'SenderId'
  > {
    return {
      SmsSdkAppid: process.env.TENCENT_CLOUD_SMS_APP_ID,
      Sign: process.env.TENCENT_CLOUD_SMS_SIGN,
      ExtendCode: process.env.TENCENT_CLOUD_SMS_EXTEND_CODE,
      SenderId: process.env.TENCENT_CLOUD_SMS_SENDER_ID,
    };
  }

  /**
   * Create Tencent Cloud SMS client.
   */
  createClient(region: string = 'ap-guangzhou'): Client {
    return new Client({
      credential: getCredential(),
      region,
      profile: {
        httpProfile: {
          endpoint: 'sms.tencentcloudapi.com',
        },
      },
    });
  }

  /**
   * Send SMS.
   * @param params Send SMS params.
   */
  async send(
    params: Pick<
      SendSmsRequest,
      'PhoneNumberSet' | 'TemplateID' | 'TemplateParamSet' | 'SessionContext'
    >,
    region: string = 'ap-guangzhou',
  ) {
    const client = this.createClient(region);
    const request = Object.assign({}, this.baseOptions, params);
    return await client.SendSms(request);
  }
}
