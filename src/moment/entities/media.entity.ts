import {
  createUnionType,
  Field,
  InterfaceType,
  ObjectType,
  registerEnumType,
} from '@nestjs/graphql';
import { MEDIA_TYPE_DONT_SUPPORTED } from 'src/constants';

/**
 * Media type.
 */
export enum MomentMediaType {
  VIDEO,
  IMAGE,
  AUDIO,
}

/**
 * Reguster moment media type enum.
 */
registerEnumType(MomentMediaType, {
  name: 'MomentMediaType',
  description: 'Media type.',
});

/**
 * Media common.
 */
@InterfaceType({
  description: 'Media common',
})
export class MomentMediaCommon {
  /**
   * Media type.
   */
  @Field(() => MomentMediaType, {
    description: 'Media type.',
  })
  type: MomentMediaType;
}

/**
 * Video media.
 */
@ObjectType({
  implements: [MomentMediaCommon],
  description: 'Video media',
})
export class MomentVideoMedia extends MomentMediaCommon {
  /**
   * Media cover path.
   */
  @Field(() => String, { description: 'Video cover' })
  cover: string;

  /**
   * Media video path.
   */
  @Field(() => String, { description: 'Video path' })
  video: string;
}

/**
 * Audio media.
 */
@ObjectType({
  implements: [MomentMediaCommon],
  description: 'Audio media.',
})
export class MomentAudioMedia extends MomentMediaCommon {
  /**
   * Media cover path.
   */
  @Field(() => String, { description: 'Audio cover', nullable: true })
  cover?: string;

  /**
   * Media video path.
   */
  @Field(() => String, { description: 'Audio path' })
  audio: string;
}

/**
 * Image media.
 */
@ObjectType({
  implements: [MomentMediaCommon],
  description: 'Image media.',
})
export class MomentImageMedia extends MomentMediaCommon {
  @Field(() => [String], { description: 'Images' })
  images: string[];
}

/**
 * Moment media union type.
 */
export const MomentMedia = createUnionType({
  name: 'MomentMedia',
  types: () => [MomentAudioMedia, MomentImageMedia, MomentVideoMedia],
  description: 'Moment media.',
  resolveType(value: MomentMediaCommon) {
    switch (value?.type) {
      case MomentMediaType.AUDIO:
        return MomentAudioMedia;
      case MomentMediaType.IMAGE:
        return MomentImageMedia;
      case MomentMediaType.VIDEO:
        return MomentVideoMedia;
      default:
        throw new Error(MEDIA_TYPE_DONT_SUPPORTED);
    }
  },
});
