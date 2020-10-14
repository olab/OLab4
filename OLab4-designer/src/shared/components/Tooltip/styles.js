import arrowGenerator from '../../../helpers/arrowGenerator';

import { BLUE, WHITE } from '../../colors';

const styles = theme => ({
  button: {
    margin: theme.spacing.unit,
  },
  styleTooltip: {
    backgroundColor: BLUE,
    color: WHITE,
    boxShadow: theme.shadows[1],
    fontSize: 11,
  },
  arrowPopper: arrowGenerator(BLUE),
  arrow: {
    position: 'absolute',
    fontSize: 6,
    width: '3em',
    height: '3em',
    '&::before': {
      content: '""',
      margin: 'auto',
      display: 'block',
      width: 0,
      height: 0,
      borderStyle: 'solid',
    },
  },
});

export default styles;
