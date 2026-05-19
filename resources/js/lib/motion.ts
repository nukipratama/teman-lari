import type { Transition, Variants } from 'framer-motion';

const enterEase: Transition = {
    duration: 0.32,
    ease: [0.22, 1, 0.36, 1],
};

export const fadeInUp: Variants = {
    hidden: { opacity: 0, y: 8 },
    visible: { opacity: 1, y: 0, transition: enterEase },
};

export const breath: Variants = {
    idle: {
        scale: [1, 1.02, 1],
        transition: { duration: 2.5, repeat: Infinity, ease: 'easeInOut' },
    },
};

export const idleByMood: Record<string, Variants> = {
    glow: {
        idle: {
            y: [0, -3, 0],
            scale: [1, 1.025, 1],
            transition: { duration: 1.6, repeat: Infinity, ease: 'easeInOut' },
        },
    },
    bouncy: {
        idle: {
            y: [0, -4, 0],
            transition: { duration: 1.2, repeat: Infinity, ease: 'easeOut' },
        },
    },
    dim: {
        idle: {
            rotate: [-2, 2, -2],
            transition: { duration: 4.5, repeat: Infinity, ease: 'easeInOut' },
        },
    },
    spinning: {
        idle: {
            rotate: [0, 360],
            transition: { duration: 12, repeat: Infinity, ease: 'linear' },
        },
    },
    wobble: {
        idle: {
            rotate: [-3, 3, -3, 0],
            transition: { duration: 1.4, repeat: Infinity, repeatDelay: 1.5, ease: 'easeInOut' },
        },
    },
    squished: breath,
};

export const pressShrink = { scale: 0.97 };
