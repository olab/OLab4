import { MIDDLE_LIGHT_GREY } from '../../../shared/colors';

const styles = () => ({
  paper: {
    height: '79.5vh',
    overflow: 'auto',
  },
  tableRow: {
    '&:hover': {
      backgroundColor: `${MIDDLE_LIGHT_GREY}`,
    },
  },
});

export default styles;
