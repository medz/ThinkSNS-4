import { Field, ObjectType } from '@nestjs/graphql';
import { TencentCloudStsFederationToken } from '../../sts';

@ObjectType()
export class TencentCloudCosCredentials {
  @Field(() => TencentCloudStsFederationToken, {
    description: 'Tencent Cloud STS authorization token',
  })
  authorization: TencentCloudStsFederationToken;

  @Field(() => String, {
    description: 'Tencent Cloud COS bucket',
  })
  bucket: string;

  @Field(() => String, {
    description: 'Tencent Cloud COS bucket region',
  })
  region: string;
}
