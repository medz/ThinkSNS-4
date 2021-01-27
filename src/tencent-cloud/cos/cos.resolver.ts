import {
  Args,
  Mutation,
  Parent,
  registerEnumType,
  ResolveField,
  Resolver,
} from '@nestjs/graphql';
import { AuthorizationWith } from 'src/authorization.decorator';
import { IDHelper } from 'src/helper';
import { TencentCloudStsFederationToken } from '../sts';
import { TencentCloudCosCredentials } from './entities/cos.entity';
import { TencentCloudCosService } from './soc.service';

export enum AllowUploadFileType {
  // 图片
  JPG = '.jpg',
  PNG = '.png',
  GIF = '.gif',
  // 视频
  MP4 = '.mp4',
  OGG = '.ogg',
  // 音频
  MP3 = '.mp3',
  WAV = '.wav',
}

registerEnumType(AllowUploadFileType, {
  name: 'AllowUploadFileType',
});

@Resolver(() => TencentCloudCosCredentials)
export class TencentCloudCosResolver {
  constructor(private readonly cosService: TencentCloudCosService) {}

  @ResolveField(() => TencentCloudStsFederationToken)
  authorization(
    @Parent()
    {
      authorization,
    }: {
      authorization: () => Promise<TencentCloudStsFederationToken>;
    },
  ) {
    return authorization();
  }

  @Mutation(() => TencentCloudCosCredentials, {
    description: 'Create Tencent Cloud COS read credential',
  })
  async createCosTemporaryReadCredential(): Promise<
    Omit<TencentCloudCosCredentials, 'authorization'> & {
      authorization: () => Promise<TencentCloudStsFederationToken>;
    }
  > {
    return Object.assign({}, this.cosService.options, {
      authorization: () => this.cosService.createTemporaryReadCredential(),
    });
  }

  @Mutation(() => TencentCloudCosCredentials, {
    description: 'Create Tencent Cloud COS write credential',
  })
  @AuthorizationWith()
  async createCosTemporaryWriteCredential(
    @Args({
      name: 'type',
      type: () => AllowUploadFileType,
      description: 'create resource type.',
    })
    type: AllowUploadFileType,
  ): Promise<
    Omit<TencentCloudCosCredentials, 'authorization'> & {
      authorization: () => Promise<TencentCloudStsFederationToken>;
    }
  > {
    return Object.assign({}, this.cosService.options, {
      authorization: () =>
        this.cosService.createTemporaryWriteCredential(IDHelper.id(64) + type),
    });
  }
}
