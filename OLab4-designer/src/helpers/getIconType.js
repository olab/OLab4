// @flow
import {
  Image as ImageIcon,
  Archive as ArchiveIcon,
  Audiotrack as AudiotrackIcon,
  ErrorOutline as ErrorOutlineIcon,
  OndemandVideo as OndemandVideoIcon,
  InsertDriveFile as InsertDriveFileIcon,
} from '@material-ui/icons';

const getIconType = (iconType: string): any => {
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

export default getIconType;
