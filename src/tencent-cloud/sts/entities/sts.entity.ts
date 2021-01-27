import { Field, Int, ObjectType } from '@nestjs/graphql';
import { GetFederationTokenResponse } from 'tencentcloud-sdk-nodejs/tencentcloud/services/sts/v20180813/sts_models';
import { Credentials } from 'tencentcloud-sdk-nodejs/tencentcloud/services/sts/v20180813/sts_models';

@ObjectType()
export class TencentCloudStsCredentials implements Credentials {
  @Field(() => String, {
    description: 'Tencent Cloud credentials token',
  })
  Token: string;

  @Field(() => String, {
    description: 'Tencent Cloud credentials secrect id',
  })
  TmpSecretId: string;

  @Field(() => String, {
    description: 'Tencent Cloud credentials secrect key',
  })
  TmpSecretKey: string;
}

@ObjectType()
export class TencentCloudStsFederationToken
  implements GetFederationTokenResponse {
  @Field(() => TencentCloudStsCredentials, {
    description: 'Tencent Cloud credentials',
    nullable: true,
  })
  Credentials?: TencentCloudStsCredentials;

  @Field(() => Int, {
    description: 'Tencent Cloud credentials expired Unix time.',
    nullable: true,
  })
  ExpiredTime?: number;

  @Field(() => String, {
    description: 'Tencent Cloud credentials expired ISO8601 date.',
    nullable: true,
  })
  Expiration?: string;
}
