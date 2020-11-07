import styled from 'styled-components';
import { BLUE_GREY } from '../../../shared/colors';

const styles = () => ({
  paper: {
    margin: '0 auto',
    paddingLeft: 100,
    width: '100%',
    borderRadius: 0,
    boxShadow: 'none',
    borderBottom: `1px solid ${BLUE_GREY}`,
    borderTop: `1px solid ${BLUE_GREY}`,
  },
  button: {
    margin: '.5rem 1rem .5rem 0',
    width: '10rem',
    height: '2.5rem',
  },
});

export const OtherContent = styled.div`
  display: flex;
  justify-content: flex-start;
  margin-top: 0.7rem;
`;

export const FullContainerWidth = styled.div`
  width: 100%;
`;

export default styles;
