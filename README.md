# CloudCart – AWS E-Commerce Deployment

## Project Overview
CloudCart is an e-commerce website deployed on Amazon Web Services (AWS). This project demonstrates how to design, configure, secure, and deploy a web application using AWS cloud services.

## AWS Architecture

The infrastructure was designed across two Availability Zones inside a custom Amazon VPC.

### AWS Services Used

- **Amazon VPC:** Created an isolated cloud network.
- **Public and Private Subnets:** Separated application, database, and public resources.
- **Internet Gateway:** Provided internet connectivity to public resources.
- **NAT Gateway:** Allowed private instances to access the internet outbound.
- **Application Load Balancer:** Distributed incoming HTTP traffic.
- **Amazon EC2:** Hosted the PHP website on two instances.
- **Amazon RDS (MySQL):** Hosted the application database.
- **Amazon S3:** Stored website deployment files.
- **Security Groups:** Controlled communication between resources.
- **AWS Systems Manager:** Provided secure access to private EC2 instances.

## Website Technologies

- PHP
- HTML
- CSS
- JavaScript
- MySQL
- Apache

## Infrastructure Design

The VPC contained six subnets:

- Public-A
- Public-B
- Private-App-A
- Private-App-B
- Private-Data-A
- Private-Data-B

The Application Load Balancer received incoming traffic and distributed requests between two private EC2 instances. The EC2 instances communicated with the private RDS database.

## Deployment Steps

1. Created the VPC and subnets.
2. Configured Internet Gateway and NAT Gateway.
3. Created route tables and security groups.
4. Created two private EC2 instances.
5. Installed Apache and PHP.
6. Uploaded and deployed the website from Amazon S3.
7. Created the RDS MySQL database.
8. Connected the application to the database.
9. Configured the Application Load Balancer.
10. Tested website accessibility and target health.

## Testing

- Website successfully accessed through the Application Load Balancer.
- Both EC2 instances passed target group health checks.
- Database connectivity was tested.
- Application health endpoint returned HTTP 200.

## Security

- EC2 instances had no public IP addresses.
- EC2 HTTP access was restricted to the Load Balancer security group.
- Database access was restricted to the EC2 security group.
- Systems Manager Session Manager was used for instance access.

## Project Status

The AWS infrastructure was deployed and tested successfully. After completing the project, the AWS resources were deleted to avoid ongoing infrastructure costs.

## Learning Outcomes

- AWS cloud architecture
- VPC and subnet configuration
- Cloud networking
- EC2 deployment
- RDS database integration
- Application Load Balancing
- IAM and security groups
- Secure cloud infrastructure
- AWS cost management