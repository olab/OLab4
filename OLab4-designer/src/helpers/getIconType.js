// @flow
import {
  Archive as ArchiveIcon,
  ArrowDropDown as DropDownQuestionIcon,
  Audiotrack as AudiotrackIcon,
  ContactSupport as UnknownQuestionIcon,
  DeveloperBoard as SCTQuestionIcon,
  ErrorOutline as ErrorOutlineIcon,
  Image as ImageIcon,
  InsertDriveFile as InsertDriveFileIcon,
  ListAlt as MultiSelectQuestionIcon,
  OndemandVideo as OndemandVideoIcon,
  RadioButtonChecked as SingleSelectQuestionIcon,
  ShortText as SingleLineTextQuestionIcon,
  TextFields as RichTextQuestionIcon,
  Tune as SliderQuestionIcon,
  ViewHeadline as MultiLineTextQuestionIcon,
} from '@material-ui/icons';

export const getQuestionIconType = (questionTypeId: Number) => {
  switch (questionTypeId) {
    case 1: return SingleLineTextQuestionIcon;
    case 2: return MultiLineTextQuestionIcon;
    case 3: return MultiSelectQuestionIcon;
    case 4: return SingleSelectQuestionIcon;
    case 5: return SliderQuestionIcon;
    case 6: return DropDownQuestionIcon;
    case 7: return SCTQuestionIcon;
    // case 8: 'Situational Judgement Testing',
    // case 9: 'Cumulative',
    case 10: return RichTextQuestionIcon;
    // case 11: 'Turk Talk',
    case 12: return DropDownQuestionIcon;
    // case 13: 'Multiple-choice grid',
    // case 14: 'Pick-choice grid',
    default:
      return UnknownQuestionIcon;
  }
};

export const getIconType = (iconType: string): any => {
  switch (iconType) {
    case 'png':
    case 'gif':
    case 'jpg':
      return ImageIcon;
    case 'mp3':
    case 'wav':
    case 'aac':
    case 'm4a':
      return AudiotrackIcon;
    case 'mov':
    case 'wmv':
    case 'mp4':
      return OndemandVideoIcon;
    case 'rtf':
    case 'doc':
    case 'docx':
    case 'xls':
    case 'xlsx':
    case 'ppt':
    case 'pptx':
    case 'pdf':
      return InsertDriveFileIcon;
    case 'zip':
      return ArchiveIcon;
    default:
      return ErrorOutlineIcon;
  }
};

// export default getIconType;
