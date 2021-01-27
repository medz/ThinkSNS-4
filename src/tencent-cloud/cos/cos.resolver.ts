import { Args, Mutation, registerEnumType, Resolver } from '@nestjs/graphql';
import { AuthorizationWith } from 'src/authorization.decorator';
import { IDHelper } from 'src/helper';
import { TencentCloudFederationToken } from './entities/cos.entity';
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

@Resolver(() => TencentCloudFederationToken)
export class TencentCloudCosResolver {
  constructor(private readonly cosService: TencentCloudCosService) {}

  @Mutation(() => TencentCloudFederationToken, {
    description: 'Create Tencent Cloud COS read credential',
  })
  createTemporaryReadCredential() {
    return this.cosService.createTemporaryReadCredential();
  }

  @Mutation(() => TencentCloudFederationToken, {
    description: 'Create Tencent Cloud COS write credential',
  })
  @AuthorizationWith()
  createTemporaryWriteCredential(
    @Args({
      name: 'type',
      type: () => AllowUploadFileType,
      description: 'create resource type.',
    })
    type: AllowUploadFileType,
  ) {
    return this.cosService.createTemporaryWriteCredential(
      IDHelper.id(64) + type,
    );
  }
}
