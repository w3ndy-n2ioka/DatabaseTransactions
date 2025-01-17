FROM ubuntu:20.04

# Update package list
RUN apt-get update

# Install necessary packages
RUN apt-get install -y \
    wget \
    curl \
    nano \
    vim \
    sudo \
    iputils-ping \
    ufw \
    iproute2

# Clean up APT when done
RUN apt-get clean

# Set root password
RUN echo 'root:root' | chpasswd

EXPOSE 3306

CMD ["bash"]