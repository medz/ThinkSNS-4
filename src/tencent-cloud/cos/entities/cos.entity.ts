import { Field, ObjectType } from '@nestjs/graphql';
import { TencentCloudStsFederationToken } from '../../sts';

/**
 * Tencent CLoud COS credential
 */
@ObjectType({
  description: 'Tencent CLoud COS credential',
})
export class TencentCloudCosCredentials {
  /**
   * Tencent Cloud STS authorization token
   */
  @Field(() => TencentCloudStsFederationToken, {
    description: 'Tencent Cloud STS authorization token',
  })
  authorization: TencentCloudStsFederationToken;

  /**
   * Tencent Cloud COS bucket
   */
  @Field(() => String, {
    description: 'Tencent Cloud COS bucket',
  })
  bucket: string;

  /**
   * Tencent Cloud COS bucket region
   */
  @Field(() => String, {
    description: 'Tencent Cloud COS bucket region',
  })
  region: string;
}
