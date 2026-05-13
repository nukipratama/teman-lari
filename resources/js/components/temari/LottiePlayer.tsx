import Lottie from 'lottie-react';

// Isolated module so lottie-react / lottie-web only enter the bundle via lazy import.
export default function LottiePlayer({ animationData }: Readonly<{ animationData: unknown }>) {
    return <Lottie animationData={animationData} loop className="h-full w-full" />;
}
