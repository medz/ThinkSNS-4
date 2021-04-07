import { Injectable } from '@nestjs/common';
import { IDHelper } from 'src/helper';
import { TencentCloudStsService } from '../sts';

/**
 * Tencent Cloud COS service.
 */
@Injectable()
export class TencentCloudCosService {
  /**
   * Create the COS service.
   * @param sts Tencent Cloud STS service.
   */
  constructor(private readonly sts: TencentCloudStsService) {}

  /**
   * Get COS options.
   */
  get options() {
    return {
      bucket: process.env.TENCENT_CLOUD_COS_BUCKET,
      region: process.env.TENCENT_CLOUD_COS_REGION,
    };
  }

  /**
   * get temporary credential duration seconds.
   */
  get temporaryCredentialDurationSeconds() {
    return 7200;
  }

  /**
   * Create Tencent Cloud COS temporary read credential.
   */
  createTemporaryReadCredential(name: string = '*') {
    const { bucket, region } = this.options;
    const stsClient = this.sts.createClient(region);
    const [_, uid] = bucket.split('-');
    return stsClient.GetFederationToken({
      Name: IDHelper.alphabet(32),
      DurationSeconds: this.temporaryCredentialDurationSeconds,
      Policy: JSON.stringify({
        version: '2.0',
        statement: [
          {
            effect: 'allow',
            action: [
              'name/cos:GetObject',
              'name/cos:HeadObject',
              'name/cos:OptionsObject',
            ],
            resource: [`qcs::cos:${region}:uid/${uid}:${bucket}/${name}`],
          },
        ],
      }),
    });
  }

  /**
   * Create Tencent Cloud COS temporary write credential.
   * @param name Tencent Cloud COS object key.
   */
  createTemporaryWriteCredential(name: string) {
    const { bucket, region } = this.options;
    const stsClient = this.sts.createClient(region);
    const [_, uid] = bucket.split('-');
    return stsClient.GetFederationToken({
      Name: IDHelper.alphabet(32),
      DurationSeconds: this.temporaryCredentialDurationSeconds,
      Policy: JSON.stringify({
        version: '2.0',
        statement: [
          {
            effect: 'allow',
            action: [
              //简单上传操作
              'name/cos:PutObject',
              //表单上传对象
              'name/cos:PostObject',
              //分块上传：初始化分块操作
              'name/cos:InitiateMultipartUpload',
              //分块上传：List 进行中的分块上传
              'name/cos:ListMultipartUploads',
              //分块上传：List 已上传分块操作
              'name/cos:ListParts',
              //分块上传：上传分块块操作
              'name/cos:UploadPart',
              //分块上传：完成所有分块上传操作
              'name/cos:CompleteMultipartUpload',
              //取消分块上传操作
              'name/cos:AbortMultipartUpload',
            ],
            resource: [`qcs::cos:${region}:uid/${uid}:${bucket}/${name}`],
          },
        ],
      }),
    });
  }
}
