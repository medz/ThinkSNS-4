import { Field, Int, ObjectType } from '@nestjs/graphql';
import { GetFederationTokenResponse } from 'tencentcloud-sdk-nodejs/tencentcloud/services/sts/v20180813/sts_models';
import { Credentials } from 'tencentcloud-sdk-nodejs/tencentcloud/services/sts/v20180813/sts_models';

/**
 * Tencent Cloud STS credentials.
 */
@ObjectType({
  description: 'Tencent Cloud STS credentials.',
})
export class TencentCloudStsCredentials implements Credentials {
  /**
   * Tencent Cloud credentials token
   */
  @Field(() => String, {
    description: 'Tencent Cloud credentials token',
  })
  Token: string;

  /**
   * Tencent Cloud credentials secrect id
   */
  @Field(() => String, {
    description: 'Tencent Cloud credentials secrect id',
  })
  TmpSecretId: string;

  /**
   * Tencent Cloud credentials secrect key
   */
  @Field(() => String, {
    description: 'Tencent Cloud credentials secrect key',
  })
  TmpSecretKey: string;
}

/**
 * Tencent Clous STS federation token
 */
@ObjectType({
  description: 'Tencent Clous STS federation token',
})
export class TencentCloudStsFederationToken
  implements GetFederationTokenResponse {
  /**
   * Tencent Cloud credentials
   */
  @Field(() => TencentCloudStsCredentials, {
    description: 'Tencent Cloud credentials',
    nullable: true,
  })
  Credentials?: TencentCloudStsCredentials;

  /**
   * Tencent Cloud credentials expired Unix time
   */
  @Field(() => Int, {
    description: 'Tencent Cloud credentials expired Unix time.',
    nullable: true,
  })
  ExpiredTime?: number;

  /**
   * Tencent Cloud credentials expired ISO8601 date
   */
  @Field(() => String, {
    description: 'Tencent Cloud credentials expired ISO8601 date.',
    nullable: true,
  })
  Expiration?: string;
}
